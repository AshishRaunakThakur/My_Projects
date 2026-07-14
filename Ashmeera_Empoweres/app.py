"""
Ashmeera Empowers - Main Application (SQLite Version)
"""
import os
from datetime import datetime, timezone  # ← FIXED IMPORT
from functools import wraps
from flask import Flask, render_template, request, jsonify, redirect, url_for, flash, session
from flask_login import LoginManager, login_user, logout_user, login_required, current_user
from werkzeug.utils import secure_filename
from werkzeug.security import generate_password_hash, check_password_hash
from sqlalchemy import func, or_
from dotenv import load_dotenv

load_dotenv()

# Import configurations and models
from config import Config
from models import db, User, Employer, Job, Application, SavedJob, Notification, Skill, Education, Certificate

# Initialize Flask app
app = Flask(__name__)
app.config.from_object(Config)

# Initialize database
db.init_app(app)

# Initialize Login Manager
login_manager = LoginManager()
login_manager.init_app(app)
login_manager.login_view = 'login'
login_manager.login_message = 'Please log in to access this page.'
login_manager.login_message_category = 'warning'

# ==================== Upload Configuration ====================

UPLOAD_FOLDER = 'static/uploads'
RESUME_FOLDER = os.path.join(UPLOAD_FOLDER, 'resumes')
PROFILE_FOLDER = os.path.join(UPLOAD_FOLDER, 'profiles')

os.makedirs(RESUME_FOLDER, exist_ok=True)
os.makedirs(PROFILE_FOLDER, exist_ok=True)
os.makedirs('static/images', exist_ok=True)

ALLOWED_EXTENSIONS = {'png', 'jpg', 'jpeg', 'gif', 'pdf', 'doc', 'docx'}

def allowed_file(filename):
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

@login_manager.user_loader
def load_user(user_id):
    return User.query.get(int(user_id))

# ==================== Custom Jinja2 Filters ====================

@app.template_filter('format_number')
def format_number(value):
    if value is None:
        return ''
    try:
        if isinstance(value, str):
            value = int(value)
        return f"{value:,}"
    except (ValueError, TypeError):
        return value

# ==================== Helper Functions ====================

def send_notification(user_id, title, message, type='info', link=None):
    notification = Notification(
        user_id=user_id,
        title=title,
        message=message,
        type=type,
        link=link
    )
    db.session.add(notification)
    db.session.commit()

def calculate_match_percentage(user, job):
    match_score = 0
    total_weight = 0
    
    if user.skills:
        user_skills = set([s.strip().lower() for s in user.skills.split(',')])
        job_requirements = set([r.strip().lower() for r in job.requirements.split(',')]) if job.requirements else set()
        if job_requirements:
            common_skills = user_skills.intersection(job_requirements)
            skill_match = len(common_skills) / len(job_requirements) * 60
            match_score += skill_match
            total_weight += 60
    
    if user.location and job.location:
        if user.location.lower() == job.location.lower():
            match_score += 30
    total_weight += 30
    
    if job.work_from_home and user.work_from_home:
        match_score += 10
    total_weight += 10
    
    if total_weight > 0:
        return min(100, int((match_score / total_weight) * 100))
    return 0

# ==================== Routes ====================

@app.route('/')
def index():
    jobs = Job.query.filter_by(is_active=True).order_by(Job.posted_date.desc()).limit(6).all()
    featured_jobs = Job.query.filter_by(is_active=True, is_featured=True).limit(6).all()
    total_jobs = Job.query.filter_by(is_active=True).count()
    total_users = User.query.filter_by(user_type='user').count()
    total_employers = Employer.query.count()
    
    return render_template('index.html', 
                         jobs=jobs, 
                         featured_jobs=featured_jobs,
                         total_jobs=total_jobs,
                         total_users=total_users,
                         total_employers=total_employers)

@app.route('/login', methods=['GET', 'POST'])
def login():
    if current_user.is_authenticated:
        return redirect(url_for('dashboard'))
    
    if request.method == 'POST':
        email = request.form.get('email')
        password = request.form.get('password')
        remember = True if request.form.get('remember') else False
        
        user = User.query.filter_by(email=email).first()
        
        if not user or not user.check_password(password):
            flash('Invalid email or password. Please try again.', 'danger')
            return render_template('auth/login.html')
        
        if not user.is_active:
            flash('Your account has been deactivated.', 'danger')
            return render_template('auth/login.html')
        
        login_user(user, remember=remember)
        user.last_login = datetime.now(timezone.utc)  # ← FIXED
        db.session.commit()
        
        flash(f'Welcome back, {user.full_name}! 🎉', 'success')
        
        if user.user_type == 'admin':
            return redirect(url_for('admin_dashboard'))
        elif user.user_type == 'employer':
            return redirect(url_for('employer_dashboard'))
        else:
            return redirect(url_for('user_dashboard'))
    
    return render_template('auth/login.html')

@app.route('/register', methods=['GET', 'POST'])
def register():
    if current_user.is_authenticated:
        return redirect(url_for('dashboard'))
    
    if request.method == 'POST':
        email = request.form.get('email')
        password = request.form.get('password')
        confirm_password = request.form.get('confirm_password')
        full_name = request.form.get('full_name')
        phone = request.form.get('phone')
        user_type = request.form.get('user_type', 'user')
        
        if not all([email, password, full_name]):
            flash('Please fill all required fields.', 'danger')
            return render_template('auth/register.html')
        
        if password != confirm_password:
            flash('Passwords do not match.', 'danger')
            return render_template('auth/register.html')
        
        if len(password) < 8:
            flash('Password must be at least 8 characters long.', 'danger')
            return render_template('auth/register.html')
        
        if User.query.filter_by(email=email).first():
            flash('Email already registered.', 'warning')
            return render_template('auth/register.html')
        
        user = User(
            email=email,
            full_name=full_name,
            phone=phone,
            user_type=user_type,
            is_verified=False
        )
        user.set_password(password)
        
        db.session.add(user)
        db.session.commit()
        
        if user_type == 'employer':
            company_name = request.form.get('company_name')
            industry = request.form.get('industry')
            
            employer = Employer(
                user_id=user.id,
                company_name=company_name or full_name + "'s Company",
                industry=industry
            )
            db.session.add(employer)
            db.session.commit()
        
        send_notification(user.id, 'Welcome to Ashmeera Empowers!', 
                         'Thank you for joining our platform.', 'success')
        
        flash('Registration successful! Please login.', 'success')
        return redirect(url_for('login'))
    
    return render_template('auth/register.html')

# ==================== Forgot Password Routes ====================

@app.route('/forgot-password', methods=['GET', 'POST'])
def forgot_password():
    if current_user.is_authenticated:
        return redirect(url_for('dashboard'))
    
    if request.method == 'POST':
        email = request.form.get('email')
        
        user = User.query.filter_by(email=email).first()
        
        if not user:
            flash('No account found with this email address.', 'warning')
            return render_template('auth/forgot_password.html')
        
        if not user.security_question:
            flash('Security question not set for this account. Please contact support.', 'danger')
            return render_template('auth/forgot_password.html')
        
        session['reset_user_id'] = user.id
        return redirect(url_for('verify_security'))
    
    return render_template('auth/forgot_password.html')

@app.route('/verify-security', methods=['GET', 'POST'])
def verify_security():
    if current_user.is_authenticated:
        return redirect(url_for('dashboard'))
    
    user_id = session.get('reset_user_id')
    if not user_id:
        flash('Session expired. Please try again.', 'danger')
        return redirect(url_for('forgot_password'))
    
    user = User.query.get(user_id)
    if not user:
        flash('User not found.', 'danger')
        return redirect(url_for('forgot_password'))
    
    if request.method == 'POST':
        answer = request.form.get('answer', '').strip().lower()
        
        if answer == user.security_answer.lower():
            session['reset_verified'] = True
            return redirect(url_for('reset_password_simple'))
        else:
            flash('Incorrect answer. Please try again.', 'danger')
            return render_template('auth/verify_security.html', 
                                 user_id=user_id,
                                 security_question=user.security_question)
    
    return render_template('auth/verify_security.html', 
                         user_id=user_id,
                         security_question=user.security_question)

@app.route('/reset-password-simple', methods=['GET', 'POST'])
def reset_password_simple():
    if current_user.is_authenticated:
        return redirect(url_for('dashboard'))
    
    if not session.get('reset_verified'):
        flash('Please complete security verification first.', 'danger')
        return redirect(url_for('forgot_password'))
    
    user_id = session.get('reset_user_id')
    if not user_id:
        flash('Session expired. Please try again.', 'danger')
        return redirect(url_for('forgot_password'))
    
    user = User.query.get(user_id)
    if not user:
        flash('User not found.', 'danger')
        return redirect(url_for('forgot_password'))
    
    if request.method == 'POST':
        password = request.form.get('password')
        confirm = request.form.get('confirm_password')
        
        if password != confirm:
            flash('Passwords do not match.', 'danger')
            return render_template('auth/reset_password_simple.html', user_id=user_id)
        
        if len(password) < 8:
            flash('Password must be at least 8 characters long.', 'danger')
            return render_template('auth/reset_password_simple.html', user_id=user_id)
        
        user.set_password(password)
        db.session.commit()
        
        session.pop('reset_user_id', None)
        session.pop('reset_verified', None)
        
        flash('Password reset successfully! You can now login with your new password.', 'success')
        return redirect(url_for('login'))
    
    return render_template('auth/reset_password_simple.html', user_id=user_id)

@app.route('/logout')
@login_required
def logout():
    logout_user()
    flash('You have been logged out securely 🔒', 'info')
    return redirect(url_for('index'))

@app.route('/dashboard')
@login_required
def dashboard():
    if current_user.user_type == 'admin':
        return redirect(url_for('admin_dashboard'))
    elif current_user.user_type == 'employer':
        return redirect(url_for('employer_dashboard'))
    else:
        return redirect(url_for('user_dashboard'))

# ==================== User Routes ====================

@app.route('/user/dashboard')
@login_required
def user_dashboard():
    if current_user.user_type != 'user':
        flash('You do not have permission to access this page.', 'danger')
        return redirect(url_for('dashboard'))
    
    applications = Application.query.filter_by(user_id=current_user.id).all()
    saved_jobs = SavedJob.query.filter_by(user_id=current_user.id).all()
    notifications = Notification.query.filter_by(user_id=current_user.id, is_read=False).all()
    
    total_applications = len(applications)
    pending_applications = len([a for a in applications if a.status == 'pending'])
    shortlisted_applications = len([a for a in applications if a.status == 'shortlisted'])
    saved_count = len(saved_jobs)
    
    recommended_jobs = []
    if current_user.skills:
        user_skills = set([s.strip().lower() for s in current_user.skills.split(',')])
        all_jobs = Job.query.filter_by(is_active=True).all()
        for job in all_jobs:
            if job.requirements:
                job_skills = set([r.strip().lower() for r in job.requirements.split(',')])
                if user_skills.intersection(job_skills):
                    match_score = calculate_match_percentage(current_user, job)
                    recommended_jobs.append((job, match_score))
        recommended_jobs.sort(key=lambda x: x[1], reverse=True)
        recommended_jobs = recommended_jobs[:5]
    
    return render_template('user/dashboard.html',
                         applications=applications,
                         saved_jobs=saved_jobs,
                         notifications=notifications,
                         total_applications=total_applications,
                         pending_applications=pending_applications,
                         shortlisted_applications=shortlisted_applications,
                         saved_count=saved_count,
                         recommended_jobs=recommended_jobs)

@app.route('/user/profile', methods=['GET', 'POST'])
@login_required
def user_profile():
    if current_user.user_type != 'user':
        flash('You do not have permission to access this page.', 'danger')
        return redirect(url_for('dashboard'))
    
    if request.method == 'POST':
        current_user.full_name = request.form.get('full_name')
        current_user.bio = request.form.get('bio')
        current_user.location = request.form.get('location')
        current_user.phone = request.form.get('phone')
        current_user.disability_type = request.form.get('disability_type')
        current_user.preferred_job_type = request.form.get('preferred_job_type')
        current_user.preferred_location = request.form.get('preferred_location')
        current_user.expected_salary_min = request.form.get('expected_salary_min') or None
        current_user.expected_salary_max = request.form.get('expected_salary_max') or None
        current_user.work_from_home = True if request.form.get('work_from_home') else False
        current_user.skills = request.form.get('skills')
        
        current_user.security_question = request.form.get('security_question')
        if request.form.get('security_answer'):
            current_user.security_answer = request.form.get('security_answer', '').strip().lower()
        
        if 'profile_photo' in request.files:
            file = request.files['profile_photo']
            if file and allowed_file(file.filename):
                if current_user.profile_photo:
                    old_path = os.path.join(PROFILE_FOLDER, current_user.profile_photo)
                    if os.path.exists(old_path):
                        os.remove(old_path)
                
                filename = secure_filename(f"{current_user.id}_{datetime.now(timezone.utc).timestamp()}_{file.filename}")  # ← FIXED
                filepath = os.path.join(PROFILE_FOLDER, filename)
                file.save(filepath)
                current_user.profile_photo = filename
                db.session.commit()
                flash('Profile photo updated successfully!', 'success')
                return redirect(url_for('user_profile'))
        
        if 'resume' in request.files:
            file = request.files['resume']
            if file and allowed_file(file.filename):
                if current_user.resume:
                    old_path = os.path.join(RESUME_FOLDER, current_user.resume)
                    if os.path.exists(old_path):
                        os.remove(old_path)
                
                filename = secure_filename(f"{current_user.id}_{datetime.now(timezone.utc).timestamp()}_{file.filename}")  # ← FIXED
                filepath = os.path.join(RESUME_FOLDER, filename)
                file.save(filepath)
                current_user.resume = filename
                db.session.commit()
                flash('Resume uploaded successfully!', 'success')
                return redirect(url_for('user_profile'))
        
        db.session.commit()
        flash('Profile updated successfully!', 'success')
        return redirect(url_for('user_profile'))
    
    return render_template('user/profile.html')

@app.route('/user/jobs')
@login_required
def user_jobs():
    if current_user.user_type != 'user':
        flash('You do not have permission to access this page.', 'danger')
        return redirect(url_for('dashboard'))
    
    page = request.args.get('page', 1, type=int)
    per_page = 10
    search = request.args.get('search', '')
    category = request.args.get('category', '')
    location = request.args.get('location', '')
    job_type = request.args.get('job_type', '')
    experience = request.args.get('experience', '')
    work_from_home = request.args.get('work_from_home', '')
    
    query = Job.query.filter_by(is_active=True)
    
    if search:
        query = query.filter(or_(
            Job.title.ilike(f'%{search}%'),
            Job.description.ilike(f'%{search}%'),
            Job.requirements.ilike(f'%{search}%')
        ))
    if category:
        query = query.filter_by(category=category)
    if location:
        query = query.filter(Job.location.ilike(f'%{location}%'))
    if job_type:
        query = query.filter_by(job_type=job_type)
    if experience:
        query = query.filter_by(experience_level=experience)
    if work_from_home:
        query = query.filter_by(work_from_home=True)
    
    jobs = query.order_by(Job.posted_date.desc()).paginate(page=page, per_page=per_page)
    
    saved_job_ids = [s.job_id for s in SavedJob.query.filter_by(user_id=current_user.id).all()]
    applied_job_ids = [a.job_id for a in Application.query.filter_by(user_id=current_user.id).all()]
    
    categories = db.session.query(Job.category).distinct().all()
    categories = [c[0] for c in categories if c[0]]
    
    return render_template('user/jobs.html',
                         jobs=jobs,
                         saved_job_ids=saved_job_ids,
                         applied_job_ids=applied_job_ids,
                         categories=categories,
                         search=search,
                         category=category,
                         location=location,
                         job_type=job_type,
                         experience=experience,
                         work_from_home=work_from_home)

@app.route('/user/job/<int:job_id>')
@login_required
def job_detail(job_id):
    if current_user.user_type != 'user':
        flash('You do not have permission to access this page.', 'danger')
        return redirect(url_for('dashboard'))
    
    job = Job.query.get_or_404(job_id)
    is_saved = SavedJob.query.filter_by(user_id=current_user.id, job_id=job_id).first() is not None
    has_applied = Application.query.filter_by(user_id=current_user.id, job_id=job_id).first() is not None
    
    match_percentage = calculate_match_percentage(current_user, job)
    
    similar_jobs = Job.query.filter(
        Job.is_active == True,
        Job.id != job_id,
        or_(
            Job.category == job.category,
            Job.location == job.location
        )
    ).limit(4).all()
    
    return render_template('user/job_detail.html',
                         job=job,
                         is_saved=is_saved,
                         has_applied=has_applied,
                         match_percentage=match_percentage,
                         similar_jobs=similar_jobs)

@app.route('/user/job/<int:job_id>/apply', methods=['POST'])
@login_required
def apply_job(job_id):
    if current_user.user_type != 'user':
        flash('You do not have permission to access this page.', 'danger')
        return redirect(url_for('dashboard'))
    
    job = Job.query.get_or_404(job_id)
    
    existing = Application.query.filter_by(job_id=job_id, user_id=current_user.id).first()
    if existing:
        flash('You have already applied for this job.', 'warning')
        return redirect(url_for('job_detail', job_id=job_id))
    
    if not current_user.resume:
        flash('Please upload your resume before applying.', 'warning')
        return redirect(url_for('user_profile'))
    
    cover_letter = request.form.get('cover_letter', '')
    match_percentage = calculate_match_percentage(current_user, job)
    
    application = Application(
        job_id=job_id,
        user_id=current_user.id,
        cover_letter=cover_letter,
        match_percentage=match_percentage
    )
    
    db.session.add(application)
    db.session.commit()
    
    employer = Employer.query.get(job.employer_id)
    if employer and employer.user:
        send_notification(employer.user_id,
                         f'New application for {job.title}',
                         f'{current_user.full_name} has applied.',
                         'info',
                         url_for('employer_applicants', job_id=job.id))
    
    send_notification(current_user.id,
                     f'Application submitted for {job.title}',
                     f'Your application has been submitted successfully.',
                     'success',
                     url_for('user_applications'))
    
    flash('Application submitted successfully!', 'success')
    return redirect(url_for('job_detail', job_id=job_id))

@app.route('/user/job/<int:job_id>/save', methods=['POST'])
@login_required
def save_job(job_id):
    if current_user.user_type != 'user':
        return jsonify({'error': 'Unauthorized'}), 403
    
    existing = SavedJob.query.filter_by(job_id=job_id, user_id=current_user.id).first()
    if existing:
        db.session.delete(existing)
        db.session.commit()
        return jsonify({'saved': False, 'message': 'Job removed from saved.'})
    else:
        saved_job = SavedJob(job_id=job_id, user_id=current_user.id)
        db.session.add(saved_job)
        db.session.commit()
        return jsonify({'saved': True, 'message': 'Job saved successfully.'})

@app.route('/user/applications')
@login_required
def user_applications():
    if current_user.user_type != 'user':
        flash('You do not have permission to access this page.', 'danger')
        return redirect(url_for('dashboard'))
    
    applications = Application.query.filter_by(user_id=current_user.id).order_by(Application.applied_date.desc()).all()
    return render_template('user/applications.html', applications=applications)

@app.route('/user/notifications')
@login_required
def user_notifications():
    if current_user.user_type != 'user':
        flash('You do not have permission to access this page.', 'danger')
        return redirect(url_for('dashboard'))
    
    notifications = Notification.query.filter_by(user_id=current_user.id).order_by(Notification.created_at.desc()).all()
    
    for notif in notifications:
        notif.is_read = True
    db.session.commit()
    
    return render_template('user/notifications.html', notifications=notifications)

# ==================== Employer Routes ====================

@app.route('/employer/dashboard')
@login_required
def employer_dashboard():
    if current_user.user_type != 'employer':
        flash('You do not have permission to access this page.', 'danger')
        return redirect(url_for('dashboard'))
    
    employer = Employer.query.filter_by(user_id=current_user.id).first()
    if not employer:
        flash('Please complete your company profile.', 'warning')
        return redirect(url_for('employer_profile'))
    
    jobs = Job.query.filter_by(employer_id=employer.id).all()
    total_jobs = len(jobs)
    active_jobs = len([j for j in jobs if j.is_active])
    
    total_applications = Application.query.join(Job).filter(Job.employer_id == employer.id).count()
    pending_applications = Application.query.join(Job).filter(
        Job.employer_id == employer.id,
        Application.status == 'pending'
    ).count()
    
    recent_applications = Application.query.join(Job).filter(
        Job.employer_id == employer.id
    ).order_by(Application.applied_date.desc()).limit(10).all()
    
    return render_template('employer/dashboard.html',
                         employer=employer,
                         jobs=jobs,
                         total_jobs=total_jobs,
                         active_jobs=active_jobs,
                         total_applications=total_applications,
                         pending_applications=pending_applications,
                         recent_applications=recent_applications)

@app.route('/employer/profile', methods=['GET', 'POST'])
@login_required
def employer_profile():
    if current_user.user_type != 'employer':
        flash('You do not have permission to access this page.', 'danger')
        return redirect(url_for('dashboard'))
    
    employer = Employer.query.filter_by(user_id=current_user.id).first()
    if not employer:
        employer = Employer(user_id=current_user.id)
        db.session.add(employer)
        db.session.commit()
    
    if request.method == 'POST':
        employer.company_name = request.form.get('company_name')
        employer.company_description = request.form.get('company_description')
        employer.company_website = request.form.get('company_website')
        employer.company_size = request.form.get('company_size')
        employer.industry = request.form.get('industry')
        employer.headquarters = request.form.get('headquarters')
        
        if 'company_logo' in request.files:
            file = request.files['company_logo']
            if file and allowed_file(file.filename):
                filename = secure_filename(f"company_{employer.id}_{datetime.now(timezone.utc).timestamp()}_{file.filename}")  # ← FIXED
                filepath = os.path.join(PROFILE_FOLDER, filename)
                file.save(filepath)
                employer.company_logo = filename
        
        db.session.commit()
        flash('Company profile updated successfully!', 'success')
        return redirect(url_for('employer_profile'))
    
    return render_template('employer/company_profile.html', employer=employer)

@app.route('/employer/post-job', methods=['GET', 'POST'])
@login_required
def post_job():
    if current_user.user_type != 'employer':
        flash('You do not have permission to access this page.', 'danger')
        return redirect(url_for('dashboard'))
    
    employer = Employer.query.filter_by(user_id=current_user.id).first()
    if not employer:
        flash('Please complete your company profile first.', 'warning')
        return redirect(url_for('employer_profile'))
    
    if request.method == 'POST':
        job = Job(
            employer_id=employer.id,
            title=request.form.get('title'),
            description=request.form.get('description'),
            requirements=request.form.get('requirements'),
            category=request.form.get('category'),
            job_type=request.form.get('job_type'),
            experience_level=request.form.get('experience_level'),
            location=request.form.get('location'),
            salary_min=request.form.get('salary_min') or None,
            salary_max=request.form.get('salary_max') or None,
            work_from_home=True if request.form.get('work_from_home') else False,
            disability_friendly=True if request.form.get('disability_friendly') else False,
            is_active=True
        )
        
        db.session.add(job)
        db.session.commit()
        
        flash('Job posted successfully!', 'success')
        return redirect(url_for('employer_dashboard'))
    
    return render_template('employer/post_job.html', employer=employer)

@app.route('/employer/jobs')
@login_required
def employer_jobs():
    if current_user.user_type != 'employer':
        flash('You do not have permission to access this page.', 'danger')
        return redirect(url_for('dashboard'))
    
    employer = Employer.query.filter_by(user_id=current_user.id).first()
    if not employer:
        return redirect(url_for('employer_profile'))
    
    jobs = Job.query.filter_by(employer_id=employer.id).order_by(Job.posted_date.desc()).all()
    return render_template('employer/manage_jobs.html', jobs=jobs)

@app.route('/employer/job/<int:job_id>/edit', methods=['GET', 'POST'])
@login_required
def edit_job(job_id):
    if current_user.user_type != 'employer':
        flash('You do not have permission to access this page.', 'danger')
        return redirect(url_for('dashboard'))
    
    job = Job.query.get_or_404(job_id)
    employer = Employer.query.filter_by(user_id=current_user.id).first()
    
    if job.employer_id != employer.id:
        flash('Unauthorized access.', 'danger')
        return redirect(url_for('employer_dashboard'))
    
    if request.method == 'POST':
        job.title = request.form.get('title')
        job.description = request.form.get('description')
        job.requirements = request.form.get('requirements')
        job.category = request.form.get('category')
        job.job_type = request.form.get('job_type')
        job.experience_level = request.form.get('experience_level')
        job.location = request.form.get('location')
        job.salary_min = request.form.get('salary_min') or None
        job.salary_max = request.form.get('salary_max') or None
        job.work_from_home = True if request.form.get('work_from_home') else False
        job.disability_friendly = True if request.form.get('disability_friendly') else False
        
        db.session.commit()
        flash('Job updated successfully!', 'success')
        return redirect(url_for('employer_jobs'))
    
    return render_template('employer/edit_job.html', job=job)

@app.route('/employer/applicants')
@login_required
def employer_applicants():
    if current_user.user_type != 'employer':
        flash('You do not have permission to access this page.', 'danger')
        return redirect(url_for('dashboard'))
    
    employer = Employer.query.filter_by(user_id=current_user.id).first()
    if not employer:
        flash('Please complete your company profile.', 'warning')
        return redirect(url_for('employer_profile'))
    
    job_id = request.args.get('job_id')
    status = request.args.get('status')
    
    query = Application.query.join(Job).filter(Job.employer_id == employer.id)
    
    if job_id:
        query = query.filter(Application.job_id == job_id)
    if status:
        query = query.filter(Application.status == status)
    
    applications = query.order_by(Application.applied_date.desc()).all()
    
    jobs = Job.query.filter_by(employer_id=employer.id).all()
    
    return render_template('employer/applicants.html',
                         applications=applications,
                         jobs=jobs,
                         selected_job=job_id,
                         selected_status=status)

@app.route('/employer/application/<int:app_id>/status', methods=['POST'])
@login_required
def update_application_status(app_id):
    if current_user.user_type != 'employer':
        return jsonify({'error': 'Unauthorized'}), 403
    
    application = Application.query.get_or_404(app_id)
    employer = Employer.query.filter_by(user_id=current_user.id).first()
    
    job = Job.query.get(application.job_id)
    if not job or job.employer_id != employer.id:
        return jsonify({'error': 'Unauthorized'}), 403
    
    status = request.form.get('status')
    if status not in ['pending', 'shortlisted', 'approved', 'rejected']:
        return jsonify({'error': 'Invalid status'}), 400
    
    application.status = status
    db.session.commit()
    
    status_messages = {
        'shortlisted': 'Congratulations! You have been shortlisted.',
        'approved': 'Great news! Your application has been approved.',
        'rejected': 'Your application has been rejected.'
    }
    
    if status in status_messages:
        send_notification(application.user_id,
                         f'Application status updated for {job.title}',
                         status_messages[status],
                         'info' if status == 'rejected' else 'success',
                         url_for('user_applications'))
    
    return jsonify({'success': True, 'message': 'Status updated successfully.'})

@app.route('/employer/notifications')
@login_required
def employer_notifications():
    if current_user.user_type != 'employer':
        flash('You do not have permission to access this page.', 'danger')
        return redirect(url_for('dashboard'))
    
    notifications = Notification.query.filter_by(user_id=current_user.id).order_by(Notification.created_at.desc()).all()
    for notif in notifications:
        notif.is_read = True
    db.session.commit()
    return render_template('employer/notifications.html', notifications=notifications)

# ==================== Admin Routes ====================

@app.route('/admin/dashboard')
@login_required
def admin_dashboard():
    if current_user.user_type != 'admin':
        flash('You do not have permission to access this page.', 'danger')
        return redirect(url_for('dashboard'))
    
    total_users = User.query.filter_by(user_type='user').count()
    total_employers = Employer.query.count()
    total_jobs = Job.query.count()
    total_applications = Application.query.count()
    active_jobs = Job.query.filter_by(is_active=True).count()
    
    recent_users = User.query.filter_by(user_type='user').order_by(User.created_at.desc()).limit(5).all()
    recent_applications = Application.query.order_by(Application.applied_date.desc()).limit(5).all()
    
    return render_template('admin/dashboard.html',
                         total_users=total_users,
                         total_employers=total_employers,
                         total_jobs=total_jobs,
                         total_applications=total_applications,
                         active_jobs=active_jobs,
                         recent_users=recent_users,
                         recent_applications=recent_applications)

@app.route('/admin/users')
@login_required
def admin_users():
    if current_user.user_type != 'admin':
        flash('You do not have permission to access this page.', 'danger')
        return redirect(url_for('dashboard'))
    
    users = User.query.filter_by(user_type='user').order_by(User.created_at.desc()).all()
    return render_template('admin/users.html', users=users)

@app.route('/admin/employers')
@login_required
def admin_employers():
    if current_user.user_type != 'admin':
        flash('You do not have permission to access this page.', 'danger')
        return redirect(url_for('dashboard'))
    
    employers = Employer.query.order_by(Employer.created_at.desc()).all()
    return render_template('admin/employers.html', employers=employers)

@app.route('/admin/jobs')
@login_required
def admin_jobs():
    if current_user.user_type != 'admin':
        flash('You do not have permission to access this page.', 'danger')
        return redirect(url_for('dashboard'))
    
    jobs = Job.query.order_by(Job.posted_date.desc()).all()
    return render_template('admin/jobs.html', jobs=jobs)

@app.route('/admin/profile', methods=['GET', 'POST'])
@login_required
def admin_profile():
    if current_user.user_type != 'admin':
        flash('You do not have permission to access this page.', 'danger')
        return redirect(url_for('dashboard'))
    
    if request.method == 'POST':
        current_user.full_name = request.form.get('full_name')
        current_user.bio = request.form.get('bio')
        current_user.location = request.form.get('location')
        current_user.phone = request.form.get('phone')
        current_user.security_question = request.form.get('security_question')
        if request.form.get('security_answer'):
            current_user.security_answer = request.form.get('security_answer', '').strip().lower()
        
        if 'profile_photo' in request.files:
            file = request.files['profile_photo']
            if file and allowed_file(file.filename):
                if current_user.profile_photo:
                    old_path = os.path.join(PROFILE_FOLDER, current_user.profile_photo)
                    if os.path.exists(old_path):
                        os.remove(old_path)
                filename = secure_filename(f"{current_user.id}_{datetime.now(timezone.utc).timestamp()}_{file.filename}")  # ← FIXED
                filepath = os.path.join(PROFILE_FOLDER, filename)
                file.save(filepath)
                current_user.profile_photo = filename
                db.session.commit()
                flash('Profile updated successfully!', 'success')
                return redirect(url_for('admin_profile'))
        
        db.session.commit()
        flash('Profile updated successfully!', 'success')
        return redirect(url_for('admin_profile'))
    
    return render_template('admin/profile.html')

@app.route('/admin/settings', methods=['GET', 'POST'])
@login_required
def admin_settings():
    if current_user.user_type != 'admin':
        flash('You do not have permission to access this page.', 'danger')
        return redirect(url_for('dashboard'))
    
    total_users = User.query.filter_by(user_type='user').count()
    total_jobs = Job.query.count()
    
    if request.method == 'POST':
        flash('Settings updated successfully!', 'success')
        return redirect(url_for('admin_settings'))
    
    return render_template('admin/settings.html', total_users=total_users, total_jobs=total_jobs)

# ==================== Admin AJAX Routes ====================

@app.route('/admin/user/<int:user_id>/toggle', methods=['POST'])
@login_required
def admin_toggle_user(user_id):
    if current_user.user_type != 'admin':
        return jsonify({'error': 'Unauthorized'}), 403
    
    user = User.query.get_or_404(user_id)
    user.is_active = not user.is_active
    db.session.commit()
    return jsonify({'success': True, 'is_active': user.is_active})

@app.route('/admin/user/<int:user_id>/delete', methods=['POST'])
@login_required
def admin_delete_user(user_id):
    if current_user.user_type != 'admin':
        return jsonify({'error': 'Unauthorized'}), 403
    
    user = User.query.get_or_404(user_id)
    Application.query.filter_by(user_id=user_id).delete()
    SavedJob.query.filter_by(user_id=user_id).delete()
    Notification.query.filter_by(user_id=user_id).delete()
    db.session.delete(user)
    db.session.commit()
    return jsonify({'success': True})

@app.route('/admin/employer/<int:employer_id>/verify', methods=['POST'])
@login_required
def admin_verify_employer(employer_id):
    if current_user.user_type != 'admin':
        return jsonify({'error': 'Unauthorized'}), 403
    
    employer = Employer.query.get_or_404(employer_id)
    employer.is_verified = not employer.is_verified
    db.session.commit()
    return jsonify({'success': True, 'is_verified': employer.is_verified})

@app.route('/admin/employer/<int:employer_id>/delete', methods=['POST'])
@login_required
def admin_delete_employer(employer_id):
    if current_user.user_type != 'admin':
        return jsonify({'error': 'Unauthorized'}), 403
    
    employer = Employer.query.get_or_404(employer_id)
    Job.query.filter_by(employer_id=employer_id).delete()
    db.session.delete(employer)
    db.session.commit()
    return jsonify({'success': True})

@app.route('/admin/job/<int:job_id>/toggle', methods=['POST'])
@login_required
def admin_toggle_job(job_id):
    if current_user.user_type != 'admin':
        return jsonify({'error': 'Unauthorized'}), 403
    
    job = Job.query.get_or_404(job_id)
    job.is_active = not job.is_active
    db.session.commit()
    return jsonify({'success': True, 'is_active': job.is_active})

@app.route('/admin/job/<int:job_id>/delete', methods=['POST'])
@login_required
def admin_delete_job(job_id):
    if current_user.user_type != 'admin':
        return jsonify({'error': 'Unauthorized'}), 403
    
    job = Job.query.get_or_404(job_id)
    Application.query.filter_by(job_id=job_id).delete()
    SavedJob.query.filter_by(job_id=job_id).delete()
    db.session.delete(job)
    db.session.commit()
    return jsonify({'success': True})

# ==================== Legal Pages Routes ====================

@app.route('/privacy-policy')
def privacy_policy():
    return render_template('privacy_policy.html')

@app.route('/terms-conditions')
def terms_conditions():
    return render_template('terms_conditions.html')

# ==================== API Routes ====================

@app.route('/api/notifications/unread')
@login_required
def api_unread_notifications():
    count = Notification.query.filter_by(user_id=current_user.id, is_read=False).count()
    notifications = Notification.query.filter_by(user_id=current_user.id, is_read=False).limit(5).all()
    
    return jsonify({
        'count': count,
        'notifications': [{
            'id': n.id,
            'title': n.title,
            'message': n.message,
            'created_at': n.created_at.strftime('%Y-%m-%d %H:%M'),
            'link': n.link
        } for n in notifications]
    })

@app.route('/api/employer/notifications/mark-all-read', methods=['POST'])
@login_required
def employer_mark_all_read():
    if current_user.user_type != 'employer':
        return jsonify({'error': 'Unauthorized'}), 403
    
    Notification.query.filter_by(user_id=current_user.id, is_read=False).update({'is_read': True})
    db.session.commit()
    return jsonify({'success': True})

@app.route('/api/employer/notifications/<int:notif_id>/dismiss', methods=['POST'])
@login_required
def employer_dismiss_notification(notif_id):
    if current_user.user_type != 'employer':
        return jsonify({'error': 'Unauthorized'}), 403
    
    notification = Notification.query.get_or_404(notif_id)
    if notification.user_id != current_user.id:
        return jsonify({'error': 'Unauthorized'}), 403
    db.session.delete(notification)
    db.session.commit()
    return jsonify({'success': True})

# ==================== Error Handlers ====================

@app.errorhandler(404)
def not_found(error):
    return render_template('errors/404.html'), 404

@app.errorhandler(500)
def internal_error(error):
    db.session.rollback()
    return render_template('errors/500.html'), 500

# ==================== Context Processors ====================

@app.context_processor
def utility_processor():
    def get_notification_count():
        if current_user.is_authenticated:
            return Notification.query.filter_by(user_id=current_user.id, is_read=False).count()
        return 0
    
    def get_profile_completion():
        if current_user.is_authenticated:
            return current_user.profile_completion
        return 0
    
    return dict(
        get_notification_count=get_notification_count,
        get_profile_completion=get_profile_completion,
        now=datetime.now(timezone.utc)  # ← FIXED
    )

# ==================== Main ====================

if __name__ == '__main__':
    with app.app_context():
        db.create_all()
        
        # Create admin
        if not User.query.filter_by(email='admin@ashmeera.com').first():
            admin = User(
                email='admin@ashmeera.com',
                full_name='Admin',
                user_type='admin',
                is_verified=True,
                is_active=True
            )
            admin.set_password('admin123')
            db.session.add(admin)
            db.session.commit()
            print("✅ Admin created: admin@ashmeera.com / admin123")
        
        # Create employer
        if not User.query.filter_by(email='company@ashmeera.com').first():
            emp_user = User(
                email='company@ashmeera.com',
                full_name='Tech Corp',
                user_type='employer',
                is_verified=True,
                is_active=True
            )
            emp_user.set_password('company123')
            db.session.add(emp_user)
            db.session.commit()
            
            employer = Employer(
                user_id=emp_user.id,
                company_name='Tech Corp India',
                industry='Technology',
                is_verified=True
            )
            db.session.add(employer)
            db.session.commit()
            print("✅ Employer created: company@ashmeera.com / company123")
        
        # Create user
        if not User.query.filter_by(email='john@example.com').first():
            john = User(
                email='john@example.com',
                full_name='John Doe',
                user_type='user',
                is_verified=True,
                is_active=True,
                skills='Python, JavaScript, HTML, CSS, React',
                location='Mumbai',
                security_question='What is your favorite color?',
                security_answer='blue'
            )
            john.set_password('john123')
            db.session.add(john)
            db.session.commit()
            print("✅ User created: john@example.com / john123")
            print("   Security Question: What is your favorite color?")
            print("   Security Answer: blue")
        
        # Create sample jobs
        if Job.query.count() == 0:
            employer = Employer.query.first()
            if employer:
                jobs = [
                    Job(
                        employer_id=employer.id,
                        title='Senior Python Developer',
                        description='We are seeking a talented Senior Python Developer to join our team.',
                        requirements='Python, Flask, SQL, JavaScript, 5+ years',
                        category='Technology',
                        job_type='Full-time',
                        experience_level='Senior',
                        location='Mumbai',
                        salary_min=800000,
                        salary_max=1500000,
                        is_active=True,
                        is_featured=True,
                        disability_friendly=True
                    ),
                    Job(
                        employer_id=employer.id,
                        title='Frontend Developer',
                        description='Exciting opportunity for a skilled Frontend Developer.',
                        requirements='HTML5, CSS3, JavaScript, React, 2+ years',
                        category='Technology',
                        job_type='Full-time',
                        experience_level='Mid',
                        location='Pune',
                        salary_min=600000,
                        salary_max=1000000,
                        is_active=True,
                        is_featured=False,
                        disability_friendly=True
                    ),
                    Job(
                        employer_id=employer.id,
                        title='UX Designer',
                        description='Looking for a passionate UX Designer.',
                        requirements='Figma, Adobe XD, 3+ years experience',
                        category='Design',
                        job_type='Full-time',
                        experience_level='Mid',
                        location='Bangalore',
                        salary_min=700000,
                        salary_max=1200000,
                        is_active=True,
                        is_featured=False,
                        disability_friendly=True
                    ),
                    Job(
                        employer_id=employer.id,
                        title='Data Analyst',
                        description='Join our data team to analyze platform accessibility.',
                        requirements='Python, SQL, Excel, 2+ years',
                        category='Data Science',
                        job_type='Full-time',
                        experience_level='Mid',
                        location='Remote',
                        salary_min=700000,
                        salary_max=1200000,
                        is_active=True,
                        is_featured=False,
                        disability_friendly=True
                    )
                ]
                for job in jobs:
                    db.session.add(job)
                db.session.commit()
                print("✅ Sample jobs created!")
    
    print("\n" + "=" * 50)
    print("🚀 Ashmeera Empowers is running!")
    print("📍 http://localhost:5000")
    print("=" * 50)
    print("\n🔑 Login Credentials:")
    print("   👑 Admin: admin@ashmeera.com / admin123")
    print("   🏢 Employer: company@ashmeera.com / company123")
    print("   👤 User: john@example.com / john123")
    print("   🔐 Security Question: What is your favorite color?")
    print("   🔐 Security Answer: blue")
    print("=" * 50)
    
    app.run(debug=True, host='0.0.0.0', port=5000)