from flask import Flask, render_template, request, redirect, url_for, flash, session, jsonify
from flask_sqlalchemy import SQLAlchemy
from flask_login import LoginManager, UserMixin, login_user, login_required, logout_user, current_user
from werkzeug.security import generate_password_hash, check_password_hash
from datetime import datetime
import json
import re
import os
import random
from dotenv import load_dotenv

load_dotenv()

app = Flask(__name__, template_folder='templates')

# Custom Jinja filter to parse JSON strings in templates
@app.template_filter('from_json')
def from_json_filter(value):
    try:
        return json.loads(value) if value else {}
    except Exception:
        return {}
    
app.config['SECRET_KEY'] = os.getenv('SECRET_KEY', 'exam-secret-key-2024-xampp')

MYSQL_USER = os.getenv('MYSQL_USER', 'root')
MYSQL_PASSWORD = os.getenv('MYSQL_PASSWORD', '')
MYSQL_HOST = os.getenv('MYSQL_HOST', 'localhost')
MYSQL_PORT = os.getenv('MYSQL_PORT', '3306')
MYSQL_DB = os.getenv('MYSQL_DB', 'online_exam_system')

app.config['SQLALCHEMY_DATABASE_URI'] = f'mysql+pymysql://{MYSQL_USER}:{MYSQL_PASSWORD}@{MYSQL_HOST}:{MYSQL_PORT}/{MYSQL_DB}'
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
app.config['SQLALCHEMY_ENGINE_OPTIONS'] = {'pool_pre_ping': True, 'pool_recycle': 300}

db = SQLAlchemy(app)
login_manager = LoginManager()
login_manager.init_app(app)
login_manager.login_view = 'login'

def get_role_from_email(email):
    """Determine role based on email domain"""
    email = email.strip().lower()
    if email.endswith('@marwadiuniversity.edu.in') or email.endswith('@marwadieducation.edu.in'):
        return 'teacher'
    elif email.endswith('@marwadiuniversity.ac.in'):
        return 'student'
    return None

def is_allowed_email(email):
    email = email.strip().lower()
    return email.endswith('@marwadiuniversity.edu.in') or email.endswith('@marwadieducation.edu.in') or email.endswith('@marwadiuniversity.ac.in')

def extract_username_from_email(email):
    local_part = email.split('@')[0]
    numbers = re.findall(r'\d+', local_part)
    if numbers:
        return max(numbers, key=len)
    return local_part

DEPARTMENTS = ['BCA', 'B.Sc IT', 'B.Sc DS', 'MCA', 'BBA', 'MBA', 'B.Tech IT', 'B.Tech CE', 'B.Tech CSE', 'B.Pharma']
SEMESTERS = ['1', '2', '3', '4', '5', '6', '7', '8']

class User(UserMixin, db.Model):
    __tablename__ = 'users'
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(80), unique=True, nullable=False)
    email = db.Column(db.String(120), unique=True, nullable=False)
    password = db.Column(db.String(200), nullable=False)
    plain_password = db.Column(db.String(200), nullable=True)
    role = db.Column(db.String(20), default='student')
    full_name = db.Column(db.String(100))
    department = db.Column(db.String(50), nullable=True)
    semester = db.Column(db.String(5), nullable=True)
    enrollment_number = db.Column(db.String(50), nullable=True)  # New field for student
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    is_active = db.Column(db.Boolean, default=True)  # For admin to disable users

class Exam(db.Model):
    __tablename__ = 'exams'
    id = db.Column(db.Integer, primary_key=True)
    title = db.Column(db.String(200), nullable=False)
    description = db.Column(db.Text)
    duration = db.Column(db.Integer)
    total_marks = db.Column(db.Integer, default=0)
    created_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    is_active = db.Column(db.Boolean, default=True)
    scheduled_date = db.Column(db.Date, nullable=True)
    start_time = db.Column(db.Time, nullable=True)
    end_time = db.Column(db.Time, nullable=True)
    target_departments = db.Column(db.Text, nullable=True)
    target_semesters = db.Column(db.Text, nullable=True)
    result_declared = db.Column(db.Boolean, default=False)
    exam_type = db.Column(db.String(20), default='normal')  # 'normal' or 'random_set'
    number_of_sets = db.Column(db.Integer, default=0)  # For random exam

class QuestionSet(db.Model):
    __tablename__ = 'question_sets'
    id = db.Column(db.Integer, primary_key=True)
    exam_id = db.Column(db.Integer, db.ForeignKey('exams.id'), nullable=False)
    set_number = db.Column(db.Integer, nullable=False)
    set_name = db.Column(db.String(50), nullable=False)  # e.g., "Set A", "Set B"
    exam = db.relationship('Exam', backref='question_sets')

class Question(db.Model):
    __tablename__ = 'questions'
    id = db.Column(db.Integer, primary_key=True)
    exam_id = db.Column(db.Integer, db.ForeignKey('exams.id'), nullable=False)
    set_id = db.Column(db.Integer, db.ForeignKey('question_sets.id'), nullable=True)  # For random exam
    question_text = db.Column(db.Text, nullable=False)
    question_type = db.Column(db.String(20), default='mcq')
    options = db.Column(db.Text)
    correct_answer = db.Column(db.Text)
    marks = db.Column(db.Integer, default=1)
    set = db.relationship('QuestionSet', backref='questions')

class Result(db.Model):
    __tablename__ = 'results'
    id = db.Column(db.Integer, primary_key=True)
    student_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    exam_id = db.Column(db.Integer, db.ForeignKey('exams.id'), nullable=False)
    set_id = db.Column(db.Integer, db.ForeignKey('question_sets.id'), nullable=True)  # Which set student got
    score = db.Column(db.Integer, default=0)
    total_marks = db.Column(db.Integer, default=0)
    answers = db.Column(db.Text)
    submitted_at = db.Column(db.DateTime, default=datetime.utcnow)
    exam = db.relationship('Exam', backref='results')
    student = db.relationship('User', foreign_keys=[student_id], backref='results')
    question_set = db.relationship('QuestionSet', backref='results')

class CheatingLog(db.Model):
    __tablename__ = 'cheating_logs'
    id = db.Column(db.Integer, primary_key=True)
    student_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    exam_id = db.Column(db.Integer, db.ForeignKey('exams.id'), nullable=False)
    violation_type = db.Column(db.String(100))
    violation_details = db.Column(db.Text)
    timestamp = db.Column(db.DateTime, default=datetime.utcnow)
    student = db.relationship('User', foreign_keys=[student_id])

@login_manager.user_loader
def load_user(user_id):
    return User.query.get(int(user_id))

# ========== HELPER FUNCTIONS ==========

def get_student_set(exam_id, enrollment_number):
    """Get question set for a student based on enrollment number"""
    exam = Exam.query.get(exam_id)
    if not exam or exam.exam_type != 'random_set':
        return None
    
    # Get all sets for this exam
    sets = QuestionSet.query.filter_by(exam_id=exam_id).all()
    if not sets:
        return None
    
    # Use enrollment number to determine set (consistent random)
    if enrollment_number:
        # Convert enrollment number to a number for consistent mapping
        hash_val = 0
        for char in str(enrollment_number):
            hash_val += ord(char)
        set_index = hash_val % len(sets)
        return sets[set_index]
    
    # Fallback to random
    return random.choice(sets)

def cleanup_empty_exams_for_teacher(teacher_id):
    """Remove draft exams that have no questions and no attempts."""
    exams = Exam.query.filter_by(created_by=teacher_id).all()
    for exam in exams:
        has_questions = Question.query.filter_by(exam_id=exam.id).first() is not None
        has_results = Result.query.filter_by(exam_id=exam.id).first() is not None
        if not has_questions and not has_results:
            QuestionSet.query.filter_by(exam_id=exam.id).delete()
            CheatingLog.query.filter_by(exam_id=exam.id).delete()
            db.session.delete(exam)
    db.session.commit()

# ========== ROUTES ==========

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/register', methods=['GET', 'POST'])
def register():
    if request.method == 'POST':
        email = request.form.get('email', '').strip().lower()
        password = request.form.get('password')
        full_name = request.form.get('full_name')
        enrollment_number = request.form.get('enrollment_number', '').strip()

        if not is_allowed_email(email):
            flash('Only Marwadi University emails are allowed (.ac.in for students, .edu.in for faculty).', 'danger')
            return redirect(url_for('register'))

        role = get_role_from_email(email)
        if not role:
            flash('Invalid email domain.', 'danger')
            return redirect(url_for('register'))

        username = extract_username_from_email(email)

        if User.query.filter_by(username=username).first():
            flash(f'An account with ID {username} is already registered. Please login.', 'danger')
            return redirect(url_for('register'))

        if User.query.filter_by(email=email).first():
            flash('This email is already registered.', 'danger')
            return redirect(url_for('register'))

        hashed_password = generate_password_hash(password)
        user = User(
            username=username, 
            email=email, 
            password=hashed_password, 
            plain_password=password,
            role=role, 
            full_name=full_name,
            enrollment_number=enrollment_number if role == 'student' else None
        )
        db.session.add(user)
        db.session.commit()

        flash(f'Registration successful! Your username is: {username}. Please login.', 'success')
        return redirect(url_for('login'))

    return render_template('register.html')

@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        username = request.form.get('username')
        password = request.form.get('password')
        user = User.query.filter_by(username=username).first()
        if user and check_password_hash(user.password, password):
            if not user.is_active:
                flash('Your account has been disabled. Please contact admin.', 'danger')
                return redirect(url_for('login'))
            login_user(user)
            if user.role == 'admin':
                return redirect(url_for('admin_dashboard'))
            elif user.role == 'teacher':
                return redirect(url_for('teacher_dashboard'))
            else:
                return redirect(url_for('student_dashboard'))
        else:
            flash('Invalid username or password', 'danger')
    return render_template('login.html')

@app.route('/logout')
@login_required
def logout():
    logout_user()
    return redirect(url_for('index'))

@app.route('/student/dashboard')
@login_required
def student_dashboard():
    if current_user.role != 'student':
        if current_user.role == 'teacher':
            return redirect(url_for('teacher_dashboard'))
        return redirect(url_for('admin_dashboard'))

    now = datetime.now()
    today = now.date()
    yesterday = today - __import__('datetime').timedelta(days=1)

    completed_exam_ids = [r.exam_id for r in Result.query.filter_by(student_id=current_user.id).all()]
    all_active = Exam.query.filter_by(is_active=True).filter(~Exam.id.in_(completed_exam_ids)).all()

    upcoming_exams = []
    live_exams = []
    missed_exams = []

    for exam in all_active:
        if exam.target_departments:
            try:
                depts = json.loads(exam.target_departments)
                if depts and current_user.department and current_user.department not in depts:
                    continue
            except Exception:
                pass
        if exam.target_semesters:
            try:
                sems = json.loads(exam.target_semesters)
                if sems and current_user.semester and current_user.semester not in sems:
                    continue
            except Exception:
                pass

        if exam.scheduled_date and exam.start_time and exam.end_time:
            start_dt = datetime.combine(exam.scheduled_date, exam.start_time)
            end_dt = datetime.combine(exam.scheduled_date, exam.end_time)
            if now < start_dt and exam.scheduled_date >= today:
                upcoming_exams.append(exam)
            elif start_dt <= now <= end_dt:
                live_exams.append(exam)
            elif end_dt < now and exam.scheduled_date >= yesterday:
                missed_exams.append(exam)

    return render_template('student_dashboard.html',
                         upcoming_exams=upcoming_exams,
                         live_exams=live_exams,
                         missed_exams=missed_exams,
                         departments=DEPARTMENTS,
                         semesters=SEMESTERS,
                         now=now)

@app.route('/exam/start/<int:exam_id>')
@login_required
def start_exam(exam_id):
    if current_user.role != 'student':
        return redirect(url_for('teacher_dashboard'))

    exam = Exam.query.get_or_404(exam_id)

    existing_result = Result.query.filter_by(student_id=current_user.id, exam_id=exam_id).first()
    if existing_result:
        flash('You have already attempted this exam.', 'warning')
        return redirect(url_for('student_dashboard'))

    # For random set exams, get the appropriate set for this student
    assigned_set = None
    questions = None
    
    if exam.exam_type == 'random_set':
        assigned_set = get_student_set(exam_id, current_user.enrollment_number)
        if assigned_set:
            questions = Question.query.filter_by(exam_id=exam_id, set_id=assigned_set.id).all()
        if not questions:
            flash('This exam has no questions yet.', 'warning')
            return redirect(url_for('student_dashboard'))
    else:
        questions = Question.query.filter_by(exam_id=exam_id, set_id=None).all()
        if not questions:
            flash('This exam has no questions yet.', 'warning')
            return redirect(url_for('student_dashboard'))

    now = datetime.now()

    if exam.scheduled_date and exam.end_time:
        end_dt = datetime.combine(exam.scheduled_date, exam.end_time)
        if now > end_dt:
            flash('The exam time has already ended.', 'danger')
            return redirect(url_for('student_dashboard'))

    if exam.scheduled_date and exam.start_time:
        start_dt = datetime.combine(exam.scheduled_date, exam.start_time)
        if now < start_dt:
            flash('The exam has not started yet. Please wait for the scheduled time.', 'warning')
            return redirect(url_for('student_dashboard'))

    return render_template('exam.html', exam=exam, questions=questions, assigned_set=assigned_set)

@app.route('/api/submit-exam', methods=['POST'])
@login_required
def submit_exam():
    if current_user.role != 'student':
        return jsonify({'error': 'Unauthorized'}), 403

    data = request.json
    exam_id = data.get('exam_id')
    answers = data.get('answers', {})
    set_id = data.get('set_id')

    exam = Exam.query.get_or_404(exam_id)
    
    # Get questions based on exam type
    if exam.exam_type == 'random_set' and set_id:
        questions = Question.query.filter_by(exam_id=exam_id, set_id=set_id).all()
    else:
        questions = Question.query.filter_by(exam_id=exam_id, set_id=None).all()

    score = 0
    total_marks = 0
    
    formatted_answers = {}
    
    for q in questions:
        qid_str = str(q.id)  # Question ID as string
        total_marks += q.marks
        
        # Check if answer exists for this question
        if qid_str in answers:
            student_ans = answers[qid_str]
            if student_ans and student_ans != '' and student_ans != 'null':
                formatted_answers[qid_str] = student_ans
                if q.question_type == 'mcq' and student_ans == q.correct_answer:
                    score += q.marks
            else:
                formatted_answers[qid_str] = None
        else:
            # Also check integer key (some browsers send integer)
            if q.id in answers:
                student_ans = answers[q.id]
                if student_ans and student_ans != '' and student_ans != 'null':
                    formatted_answers[qid_str] = student_ans
                    if q.question_type == 'mcq' and student_ans == q.correct_answer:
                        score += q.marks
                else:
                    formatted_answers[qid_str] = None
            else:
                formatted_answers[qid_str] = None

    result = Result(
        student_id=current_user.id, 
        exam_id=exam_id, 
        set_id=set_id,
        score=score,
        total_marks=total_marks, 
        answers=json.dumps(formatted_answers)
    )
    db.session.add(result)
    db.session.commit()
    
    return jsonify({'success': True, 'score': score, 'total': total_marks})

@app.route('/api/cheating-log', methods=['POST'])
@login_required
def log_cheating():
    data = request.json
    cheating_log = CheatingLog(
        student_id=current_user.id,
        exam_id=data.get('exam_id'),
        violation_type=data.get('type'),
        violation_details=data.get('details')
    )
    db.session.add(cheating_log)
    db.session.commit()
    return jsonify({'success': True})

@app.route('/profile/update', methods=['POST'])
@login_required
def update_profile():
    full_name = request.form.get('full_name', '').strip()
    new_dept = request.form.get('department', '').strip()
    new_designation = request.form.get('semester', '').strip()
    enrollment_number = request.form.get('enrollment_number', '').strip()

    if full_name:
        current_user.full_name = full_name

    if current_user.role == 'teacher':
        if new_dept in ['FOCA', 'FOET', 'FMS', 'FOP'] or new_dept == '':
            current_user.department = new_dept or None
        if new_designation in ['Assistant Professor', 'Associate Professor'] or new_designation == '':
            current_user.semester = new_designation or None
    elif current_user.role == 'student':
        if not current_user.department and new_dept in DEPARTMENTS:
            current_user.department = new_dept
        if new_designation in SEMESTERS:
            current_user.semester = new_designation
        if enrollment_number:
            current_user.enrollment_number = enrollment_number
    else:  # admin
        if new_dept in DEPARTMENTS or new_dept == '':
            current_user.department = new_dept or None

    db.session.commit()
    flash('Profile updated successfully!', 'success')
    if current_user.role == 'teacher':
        return redirect(url_for('teacher_dashboard'))
    elif current_user.role == 'admin':
        return redirect(url_for('admin_dashboard'))
    return redirect(url_for('student_dashboard'))

# ========== TEACHER ROUTES ==========

@app.route('/teacher/dashboard')
@login_required
def teacher_dashboard():
    if current_user.role != 'teacher':
        if current_user.role == 'student':
            return redirect(url_for('student_dashboard'))
        return redirect(url_for('admin_dashboard'))
    cleanup_empty_exams_for_teacher(current_user.id)
    exams = Exam.query.filter_by(created_by=current_user.id).all()
    cheating_logs = CheatingLog.query.order_by(CheatingLog.timestamp.desc()).limit(20).all()
    return render_template('teacher_dashboard.html', exams=exams, cheating_logs=cheating_logs,
                           departments=DEPARTMENTS, semesters=SEMESTERS)

@app.route('/teacher/create-exam', methods=['GET', 'POST'])
@login_required
def create_exam():
    if current_user.role != 'teacher':
        return redirect(url_for('student_dashboard'))

    if request.method == 'POST':
        exam_type = request.form.get('exam_type', 'normal')
        title = request.form.get('title')
        description = request.form.get('description')
        scheduled_date_str = request.form.get('scheduled_date')
        start_time_str = request.form.get('start_time')
        end_time_str = request.form.get('end_time')
        number_of_sets = int(request.form.get('number_of_sets', 0))

        scheduled_date = None
        start_time = None
        end_time = None
        duration = 60

        if scheduled_date_str:
            scheduled_date = datetime.strptime(scheduled_date_str, '%Y-%m-%d').date()
        if start_time_str:
            start_time = datetime.strptime(start_time_str, '%H:%M').time()
        if end_time_str:
            end_time = datetime.strptime(end_time_str, '%H:%M').time()
        if start_time and end_time and scheduled_date:
            start_dt = datetime.combine(scheduled_date, start_time)
            end_dt = datetime.combine(scheduled_date, end_time)
            diff = int((end_dt - start_dt).total_seconds() / 60)
            if diff > 0:
                duration = diff

        target_depts = request.form.getlist('target_departments[]')
        target_sems = request.form.getlist('target_semesters[]')
        target_departments_json = json.dumps(target_depts) if target_depts else None
        target_semesters_json = json.dumps(target_sems) if target_sems else None

        exam = Exam(
            title=title, description=description, duration=duration,
            created_by=current_user.id, scheduled_date=scheduled_date,
            start_time=start_time, end_time=end_time,
            target_departments=target_departments_json,
            target_semesters=target_semesters_json,
            exam_type=exam_type,
            number_of_sets=number_of_sets if exam_type == 'random_set' else 0
        )
        db.session.add(exam)
        db.session.commit()

        if exam_type == 'random_set':
            # Create question sets
            for i in range(1, number_of_sets + 1):
                set_name = f"Set {chr(64 + i)}"  # Set A, Set B, etc.
                question_set = QuestionSet(
                    exam_id=exam.id,
                    set_number=i,
                    set_name=set_name
                )
                db.session.add(question_set)
            db.session.commit()

        flash('Exam created successfully! Now add questions.', 'success')
        if exam_type == 'random_set':
            return redirect(url_for('add_questions_random', exam_id=exam.id))
        else:
            return redirect(url_for('add_questions_normal', exam_id=exam.id))

    return render_template('create_exam.html', departments=DEPARTMENTS, semesters=SEMESTERS)

@app.route('/teacher/edit-exam/<int:exam_id>', methods=['GET', 'POST'])
@login_required
def edit_exam(exam_id):
    if current_user.role != 'teacher':
        return redirect(url_for('student_dashboard'))
    
    exam = Exam.query.get_or_404(exam_id)
    
    if exam.created_by != current_user.id:
        flash('You can only edit your own exams.', 'danger')
        return redirect(url_for('teacher_dashboard'))
    
    if exam.exam_type == 'random_set':
        return redirect(url_for('edit_exam_random', exam_id=exam_id))

    questions = Question.query.filter_by(exam_id=exam_id, set_id=None).all()

    if request.method == 'POST':
        exam.title = request.form.get('title')
        exam.description = request.form.get('description')
        exam.is_active = request.form.get('is_active') == 'on'

        scheduled_date_str = request.form.get('scheduled_date')
        start_time_str = request.form.get('start_time')
        end_time_str = request.form.get('end_time')

        if scheduled_date_str:
            exam.scheduled_date = datetime.strptime(scheduled_date_str, '%Y-%m-%d').date()
        if start_time_str:
            exam.start_time = datetime.strptime(start_time_str, '%H:%M').time()
        if end_time_str:
            exam.end_time = datetime.strptime(end_time_str, '%H:%M').time()
        
        if exam.start_time and exam.end_time and exam.scheduled_date:
            diff = int((datetime.combine(exam.scheduled_date, exam.end_time) -
                        datetime.combine(exam.scheduled_date, exam.start_time)).total_seconds() / 60)
            if diff > 0:
                exam.duration = diff

        if 'target_departments[]' in request.form:
            target_depts = request.form.getlist('target_departments[]')
            exam.target_departments = json.dumps(target_depts) if target_depts else None
        if 'target_semesters[]' in request.form:
            target_sems = request.form.getlist('target_semesters[]')
            exam.target_semesters = json.dumps(target_sems) if target_sems else None

        questions_text = request.form.getlist('question_text[]')
        questions_type = request.form.getlist('question_type[]')
        questions_marks = request.form.getlist('question_marks[]')
        question_ids = request.form.getlist('question_id[]')
        correct_answers = request.form.getlist('correct_answer[]')
        options_a = request.form.getlist('option_a[]')
        options_b = request.form.getlist('option_b[]')
        options_c = request.form.getlist('option_c[]')
        options_d = request.form.getlist('option_d[]')

        # If form didn't include question ids, fall back to the old
        # (delete & recreate) behavior.
        if not any(qid and str(qid).isdigit() for qid in question_ids):
            Question.query.filter_by(exam_id=exam_id, set_id=None).delete()

            total_marks = 0
            for i in range(len(questions_text)):
                if questions_text[i].strip():
                    marks = int(questions_marks[i]) if questions_marks[i] else 1
                    total_marks += marks
                    
                    opts = {
                        'A': options_a[i] if i < len(options_a) else '',
                        'B': options_b[i] if i < len(options_b) else '',
                        'C': options_c[i] if i < len(options_c) else '',
                        'D': options_d[i] if i < len(options_d) else '',
                    }
                    options_json = json.dumps(opts)
                    correct = correct_answers[i] if i < len(correct_answers) else None

                    question = Question(
                        exam_id=exam_id,
                        question_text=questions_text[i],
                        question_type='mcq',
                        marks=marks,
                        correct_answer=correct,
                        options=options_json,
                        set_id=None
                    )
                    db.session.add(question)

            exam.total_marks = total_marks
            db.session.commit()
            flash(f'Exam "{exam.title}" updated successfully!', 'success')
            return redirect(url_for('teacher_dashboard'))

        # Update existing questions in-place to keep their `id` stable.
        existing_questions = Question.query.filter_by(exam_id=exam_id, set_id=None).all()
        existing_by_id = {q.id: q for q in existing_questions}
        keep_existing_ids = set()

        total_marks = 0
        for i in range(len(questions_text)):
            q_text = questions_text[i]
            if not q_text or not q_text.strip():
                continue

            marks = int(questions_marks[i]) if i < len(questions_marks) and questions_marks[i] else 1
            total_marks += marks
            
            opts = {
                'A': options_a[i] if i < len(options_a) else '',
                'B': options_b[i] if i < len(options_b) else '',
                'C': options_c[i] if i < len(options_c) else '',
                'D': options_d[i] if i < len(options_d) else '',
            }
            options_json = json.dumps(opts)
            correct = correct_answers[i] if i < len(correct_answers) else None

            qid_raw = question_ids[i] if i < len(question_ids) else ''
            qid_int = int(qid_raw) if qid_raw and str(qid_raw).isdigit() else None

            # Update when question id matches an existing row.
            if qid_int is not None and qid_int in existing_by_id:
                question = existing_by_id[qid_int]
                question.question_text = q_text
                question.question_type = 'mcq'
                question.marks = marks
                question.correct_answer = correct
                question.options = options_json
                question.set_id = None
                keep_existing_ids.add(qid_int)
            else:
                # New question: create a fresh row.
                question = Question(
                    exam_id=exam_id,
                    question_text=q_text,
                    question_type='mcq',
                    marks=marks,
                    correct_answer=correct,
                    options=options_json,
                    set_id=None
                )
                db.session.add(question)

        # Delete only the questions that were removed from the editor UI.
        for q in existing_questions:
            if q.id not in keep_existing_ids:
                db.session.delete(q)

        exam.total_marks = total_marks
        db.session.commit()
        flash(f'Exam "{exam.title}" updated successfully!', 'success')
        return redirect(url_for('teacher_dashboard'))

    return render_template('edit_exam.html', exam=exam, questions=questions,
                           departments=DEPARTMENTS, semesters=SEMESTERS)

@app.route('/teacher/edit-exam-random/<int:exam_id>', methods=['GET', 'POST'])
@login_required
def edit_exam_random(exam_id):
    if current_user.role != 'teacher':
        return redirect(url_for('student_dashboard'))

    exam = Exam.query.get_or_404(exam_id)
    if exam.created_by != current_user.id:
        flash('You can only edit your own exams.', 'danger')
        return redirect(url_for('teacher_dashboard'))

    if exam.exam_type != 'random_set':
        return redirect(url_for('edit_exam', exam_id=exam_id))

    question_sets = QuestionSet.query.filter_by(exam_id=exam_id).order_by(QuestionSet.set_number.asc()).all()

    if request.method == 'POST':
        exam.title = request.form.get('title')
        exam.description = request.form.get('description')
        exam.is_active = request.form.get('is_active') == 'on'

        scheduled_date_str = request.form.get('scheduled_date')
        start_time_str = request.form.get('start_time')
        end_time_str = request.form.get('end_time')

        if scheduled_date_str:
            exam.scheduled_date = datetime.strptime(scheduled_date_str, '%Y-%m-%d').date()
        if start_time_str:
            exam.start_time = datetime.strptime(start_time_str, '%H:%M').time()
        if end_time_str:
            exam.end_time = datetime.strptime(end_time_str, '%H:%M').time()

        if exam.start_time and exam.end_time and exam.scheduled_date:
            diff = int((datetime.combine(exam.scheduled_date, exam.end_time) -
                        datetime.combine(exam.scheduled_date, exam.start_time)).total_seconds() / 60)
            if diff > 0:
                exam.duration = diff

        target_depts = request.form.getlist('target_departments[]')
        target_sems = request.form.getlist('target_semesters[]')
        exam.target_departments = json.dumps(target_depts) if target_depts else None
        exam.target_semesters = json.dumps(target_sems) if target_sems else None

        total_marks = 0
        for qset in question_sets:
            questions_text = request.form.getlist(f'question_text_set_{qset.id}[]')
            question_ids = request.form.getlist(f'question_id_set_{qset.id}[]')
            correct_answers = request.form.getlist(f'correct_answer_set_{qset.id}[]')
            options_a = request.form.getlist(f'option_a_set_{qset.id}[]')
            options_b = request.form.getlist(f'option_b_set_{qset.id}[]')
            options_c = request.form.getlist(f'option_c_set_{qset.id}[]')
            options_d = request.form.getlist(f'option_d_set_{qset.id}[]')

            existing_questions = Question.query.filter_by(exam_id=exam.id, set_id=qset.id).all()
            existing_by_id = {q.id: q for q in existing_questions}
            keep_existing_ids = set()

            set_marks = 0
            for i in range(len(questions_text)):
                q_text = questions_text[i]
                if not q_text or not q_text.strip():
                    continue

                opts = {
                    'A': options_a[i] if i < len(options_a) else '',
                    'B': options_b[i] if i < len(options_b) else '',
                    'C': options_c[i] if i < len(options_c) else '',
                    'D': options_d[i] if i < len(options_d) else '',
                }
                options_json = json.dumps(opts)
                correct = correct_answers[i] if i < len(correct_answers) else None
                marks = 1
                set_marks += marks

                qid_raw = question_ids[i] if i < len(question_ids) else ''
                qid_int = int(qid_raw) if qid_raw and str(qid_raw).isdigit() else None

                if qid_int is not None and qid_int in existing_by_id:
                    question = existing_by_id[qid_int]
                    question.question_text = q_text
                    question.question_type = 'mcq'
                    question.marks = marks
                    question.correct_answer = correct
                    question.options = options_json
                    keep_existing_ids.add(qid_int)
                else:
                    question = Question(
                        exam_id=exam.id,
                        set_id=qset.id,
                        question_text=q_text,
                        question_type='mcq',
                        marks=marks,
                        correct_answer=correct,
                        options=options_json
                    )
                    db.session.add(question)

            for q in existing_questions:
                if q.id not in keep_existing_ids:
                    db.session.delete(q)

            total_marks += set_marks

        exam.total_marks = total_marks
        db.session.commit()
        flash(f'Random exam "{exam.title}" updated successfully!', 'success')
        return redirect(url_for('teacher_dashboard'))

    questions_by_set = {}
    for qset in question_sets:
        questions_by_set[qset.id] = Question.query.filter_by(exam_id=exam.id, set_id=qset.id).all()

    return render_template(
        'edit_exam_random.html',
        exam=exam,
        question_sets=question_sets,
        questions_by_set=questions_by_set,
        departments=DEPARTMENTS,
        semesters=SEMESTERS
    )

@app.route('/teacher/add-questions-normal/<int:exam_id>', methods=['GET', 'POST'])
@login_required
def add_questions_normal(exam_id):
    if current_user.role != 'teacher':
        return redirect(url_for('student_dashboard'))
    
    exam = Exam.query.get_or_404(exam_id)
    if exam.created_by != current_user.id:
        flash('You can only add questions to your own exams.', 'danger')
        return redirect(url_for('teacher_dashboard'))

    if request.method == 'POST':
        questions_text = request.form.getlist('question_text[]')
        correct_answers = request.form.getlist('correct_answer[]')
        options_a = request.form.getlist('option_a[]')
        options_b = request.form.getlist('option_b[]')
        options_c = request.form.getlist('option_c[]')
        options_d = request.form.getlist('option_d[]')

        total_marks = 0
        for i in range(len(questions_text)):
            if questions_text[i].strip():
                marks = 1
                total_marks += marks

                opts = {
                    'A': options_a[i] if i < len(options_a) else '',
                    'B': options_b[i] if i < len(options_b) else '',
                    'C': options_c[i] if i < len(options_c) else '',
                    'D': options_d[i] if i < len(options_d) else '',
                }
                options_json = json.dumps(opts)
                correct = correct_answers[i] if i < len(correct_answers) else None

                question = Question(
                    exam_id=exam.id,
                    question_text=questions_text[i],
                    question_type='mcq',
                    marks=marks,
                    correct_answer=correct,
                    options=options_json,
                    set_id=None
                )
                db.session.add(question)

        exam.total_marks = total_marks
        db.session.commit()
        flash(f'Questions added successfully to "{exam.title}"!', 'success')
        return redirect(url_for('teacher_dashboard'))

    return render_template('add_questions_normal.html', exam=exam)

@app.route('/teacher/add-questions-random/<int:exam_id>', methods=['GET', 'POST'])
@login_required
def add_questions_random(exam_id):
    if current_user.role != 'teacher':
        return redirect(url_for('student_dashboard'))
    
    exam = Exam.query.get_or_404(exam_id)
    if exam.created_by != current_user.id:
        flash('You can only add questions to your own exams.', 'danger')
        return redirect(url_for('teacher_dashboard'))

    question_sets = QuestionSet.query.filter_by(exam_id=exam_id).all()

    if request.method == 'POST':
        total_marks = 0
        
        for qset in question_sets:
            questions_text = request.form.getlist(f'question_text_set_{qset.id}[]')
            correct_answers = request.form.getlist(f'correct_answer_set_{qset.id}[]')
            options_a = request.form.getlist(f'option_a_set_{qset.id}[]')
            options_b = request.form.getlist(f'option_b_set_{qset.id}[]')
            options_c = request.form.getlist(f'option_c_set_{qset.id}[]')
            options_d = request.form.getlist(f'option_d_set_{qset.id}[]')
            
            set_marks = 0
            for i in range(len(questions_text)):
                if questions_text[i].strip():
                    marks = 1
                    set_marks += marks

                    opts = {
                        'A': options_a[i] if i < len(options_a) else '',
                        'B': options_b[i] if i < len(options_b) else '',
                        'C': options_c[i] if i < len(options_c) else '',
                        'D': options_d[i] if i < len(options_d) else '',
                    }
                    options_json = json.dumps(opts)
                    correct = correct_answers[i] if i < len(correct_answers) else None

                    question = Question(
                        exam_id=exam.id,
                        set_id=qset.id,
                        question_text=questions_text[i],
                        question_type='mcq',
                        marks=marks,
                        correct_answer=correct,
                        options=options_json
                    )
                    db.session.add(question)
            
            total_marks += set_marks

        exam.total_marks = total_marks
        db.session.commit()
        flash(f'Questions added successfully for all sets in "{exam.title}"!', 'success')
        return redirect(url_for('teacher_dashboard'))

    return render_template('add_questions_random.html', exam=exam, question_sets=question_sets)

@app.route('/teacher/cancel-exam-creation/<int:exam_id>', methods=['POST'])
@login_required
def cancel_exam_creation(exam_id):
    if current_user.role != 'teacher':
        return redirect(url_for('student_dashboard'))

    exam = Exam.query.get_or_404(exam_id)
    if exam.created_by != current_user.id:
        flash('You can only cancel your own exam draft.', 'danger')
        return redirect(url_for('teacher_dashboard'))

    has_questions = Question.query.filter_by(exam_id=exam.id).first() is not None
    has_results = Result.query.filter_by(exam_id=exam.id).first() is not None
    if has_results:
        flash('Cannot cancel exam creation after student attempts.', 'danger')
        return redirect(url_for('teacher_dashboard'))

    if has_questions:
        flash('Exam already has questions, so draft cancel was not applied.', 'warning')
        return redirect(url_for('teacher_dashboard'))

    QuestionSet.query.filter_by(exam_id=exam.id).delete()
    CheatingLog.query.filter_by(exam_id=exam.id).delete()
    db.session.delete(exam)
    db.session.commit()

    flash(f'Exam draft "{exam.title}" was cancelled and deleted.', 'success')
    return redirect(url_for('teacher_dashboard'))

@app.route('/teacher/delete-exam/<int:exam_id>', methods=['POST'])
@login_required
def delete_exam(exam_id):
    if current_user.role != 'teacher':
        return jsonify({'error': 'Unauthorized'}), 403
    exam = Exam.query.get_or_404(exam_id)
    if exam.created_by != current_user.id:
        return jsonify({'error': 'You can only delete your own exams'}), 403
    
    # Delete related records
    for qset in exam.question_sets:
        Question.query.filter_by(set_id=qset.id).delete()
    QuestionSet.query.filter_by(exam_id=exam_id).delete()
    Question.query.filter_by(exam_id=exam_id, set_id=None).delete()
    CheatingLog.query.filter_by(exam_id=exam_id).delete()
    Result.query.filter_by(exam_id=exam_id).delete()
    db.session.delete(exam)
    db.session.commit()
    
    flash(f'Exam "{exam.title}" has been deleted successfully.', 'success')
    return redirect(url_for('teacher_dashboard'))

@app.route('/teacher/exam-analysis/<int:exam_id>')
@login_required
def exam_analysis(exam_id):
    if current_user.role != 'teacher':
        return redirect(url_for('student_dashboard'))
    exam = Exam.query.get_or_404(exam_id)
    results = Result.query.filter_by(exam_id=exam_id).all()
    cheating_logs = CheatingLog.query.filter_by(exam_id=exam_id).order_by(CheatingLog.timestamp.desc()).all()
    total_students = len(results)
    average_score = sum(r.score for r in results) / total_students if total_students > 0 else 0
    pass_rate = sum(1 for r in results if r.total_marks > 0 and r.score/r.total_marks*100 >= 40) / total_students * 100 if total_students > 0 else 0
    stats = {'total_students': total_students, 'average_score': average_score,
             'pass_rate': pass_rate, 'total_cheating': len(cheating_logs)}
    return render_template('exam_analysis.html', exam=exam, results=results,
                         cheating_logs=cheating_logs, stats=stats)


@app.route('/teacher/view-student-answers/<int:result_id>')
@login_required
def view_student_answers(result_id):
    if current_user.role != 'teacher':
        return redirect(url_for('student_dashboard'))
    
    result = Result.query.get_or_404(result_id)
    exam = Exam.query.get_or_404(result.exam_id)
    
    if exam.created_by != current_user.id:
        flash('You can only view answers for your own exams.', 'danger')
        return redirect(url_for('teacher_dashboard'))
    
    # Get questions based on set if random exam
    if exam.exam_type == 'random_set' and result.set_id:
        questions = Question.query.filter_by(exam_id=exam.id, set_id=result.set_id).all()
    else:
        questions = Question.query.filter_by(exam_id=exam.id, set_id=None).all()
    
    student_answers = json.loads(result.answers) if result.answers else {}
    
    return render_template('view_student_answers.html', 
                         result=result, 
                         exam=exam, 
                         questions=questions, 
                         student_answers=student_answers)


@app.route('/teacher/flash-result/<int:exam_id>')
@login_required
def flash_result(exam_id):
    if current_user.role != 'teacher':
        return redirect(url_for('student_dashboard'))
    
    exam = Exam.query.get_or_404(exam_id)
    if exam.created_by != current_user.id:
        flash('You can only flash results for your own exams.', 'danger')
        return redirect(url_for('teacher_dashboard'))
    
    if exam.result_declared:
        exam.result_declared = False
        flash(f'🔒 Result for "{exam.title}" has been hidden from students.', 'warning')
    else:
        exam.result_declared = True
        flash(f'✅ Result for "{exam.title}" has been flashed! Students can now view their scores.', 'success')
    
    db.session.commit()
    return redirect(url_for('exam_analysis', exam_id=exam_id))


@app.route('/teacher/reenable-student/<int:student_id>/<int:exam_id>')
@login_required
def reenable_student_for_exam(student_id, exam_id):
    if current_user.role != 'teacher':
        return redirect(url_for('student_dashboard'))
    
    exam = Exam.query.get_or_404(exam_id)
    if exam.created_by != current_user.id:
        flash('You can only modify your own exams.', 'danger')
        return redirect(url_for('teacher_dashboard'))
    
    student = User.query.get_or_404(student_id)
    
    result = Result.query.filter_by(student_id=student_id, exam_id=exam_id).first()
    if result:
        CheatingLog.query.filter_by(student_id=student_id, exam_id=exam_id).delete()
        db.session.delete(result)
        db.session.commit()
        flash(f'✅ Student "{student.full_name or student.username}" can now re-attempt the exam "{exam.title}".', 'success')
    else:
        flash(f'⚠️ No existing result found for this student.', 'warning')
    
    return redirect(url_for('exam_analysis', exam_id=exam_id))


@app.route('/teacher/reenable-all/<int:exam_id>')
@login_required
def reenable_all_students_for_exam(exam_id):
    if current_user.role != 'teacher':
        return redirect(url_for('student_dashboard'))
    
    exam = Exam.query.get_or_404(exam_id)
    if exam.created_by != current_user.id:
        flash('You can only modify your own exams.', 'danger')
        return redirect(url_for('teacher_dashboard'))
    
    results = Result.query.filter_by(exam_id=exam_id).all()
    count = len(results)
    
    for result in results:
        CheatingLog.query.filter_by(student_id=result.student_id, exam_id=exam_id).delete()
        db.session.delete(result)
    
    db.session.commit()
    flash(f'✅ All {count} students can now re-attempt the exam "{exam.title}". Previous records deleted.', 'success')
    
    return redirect(url_for('exam_analysis', exam_id=exam_id))


# ========== STUDENT RESULTS ROUTES ==========

@app.route('/student/my-results')
@login_required
def student_my_results():
    if current_user.role != 'student':
        return redirect(url_for('teacher_dashboard'))
    
    results = Result.query.filter_by(student_id=current_user.id).all()
    
    declared_results = []
    for result in results:
        exam = Exam.query.get(result.exam_id)
        if exam and exam.result_declared:
            declared_results.append(result)
    
    declared_results.sort(key=lambda x: x.submitted_at, reverse=True)
    
    return render_template('student_results.html', results=declared_results)


@app.route('/student/view-result/<int:result_id>')
@login_required
def view_student_result(result_id):
    if current_user.role != 'student':
        return redirect(url_for('teacher_dashboard'))
    
    result = Result.query.get_or_404(result_id)
    
    if result.student_id != current_user.id:
        flash('You can only view your own results.', 'danger')
        return redirect(url_for('student_dashboard'))
    
    exam = Exam.query.get_or_404(result.exam_id)
    
    if not exam.result_declared:
        flash('⚠️ Result has not been declared yet by the teacher. Please check back later.', 'warning')
        return redirect(url_for('student_my_results'))
    
    # Get questions based on set if random exam
    if exam.exam_type == 'random_set' and result.set_id:
        questions = Question.query.filter_by(exam_id=exam.id, set_id=result.set_id).all()
    else:
        questions = Question.query.filter_by(exam_id=exam.id, set_id=None).all()
    
    student_answers = json.loads(result.answers) if result.answers else {}
    
    return render_template('student_result_detail.html', 
                         result=result, 
                         exam=exam, 
                         questions=questions, 
                         student_answers=student_answers)


# ========== ADMIN ROUTES ==========

@app.route('/admin/dashboard')
@login_required
def admin_dashboard():
    if current_user.role != 'admin':
        if current_user.role == 'teacher':
            return redirect(url_for('teacher_dashboard'))
        return redirect(url_for('student_dashboard'))
    
    users = User.query.all()
    exams = Exam.query.all()
    results = Result.query.all()
    cheating_logs = CheatingLog.query.all()
    
    return render_template('admin_dashboard.html', 
                         users=users, 
                         exams=exams, 
                         results=results, 
                         cheating_logs=cheating_logs,
                         departments=DEPARTMENTS,
                         semesters=SEMESTERS)


@app.route('/admin/toggle-user/<int:user_id>')
@login_required
def admin_toggle_user(user_id):
    if current_user.role != 'admin':
        return redirect(url_for('student_dashboard'))
    
    user = User.query.get_or_404(user_id)
    if user.role == 'admin':
        flash('Cannot disable admin account.', 'danger')
        return redirect(url_for('admin_dashboard'))
    
    user.is_active = not user.is_active
    db.session.commit()
    status = "enabled" if user.is_active else "disabled"
    flash(f'User "{user.username}" has been {status}.', 'success')
    
    return redirect(url_for('admin_dashboard'))


@app.route('/admin/delete-user/<int:user_id>')
@login_required
def admin_delete_user(user_id):
    if current_user.role != 'admin':
        return redirect(url_for('student_dashboard'))
    
    user = User.query.get_or_404(user_id)
    if user.role == 'admin':
        flash('Cannot delete admin account.', 'danger')
        return redirect(url_for('admin_dashboard'))
    
    # Delete related records
    Result.query.filter_by(student_id=user_id).delete()
    CheatingLog.query.filter_by(student_id=user_id).delete()
    db.session.delete(user)
    db.session.commit()
    
    flash(f'User "{user.username}" has been deleted.', 'success')
    return redirect(url_for('admin_dashboard'))

@app.route('/admin/reset-user-password/<int:user_id>', methods=['POST'])
@login_required
def admin_reset_user_password(user_id):
    if current_user.role != 'admin':
        return redirect(url_for('student_dashboard'))

    user = User.query.get_or_404(user_id)
    if user.role == 'admin':
        flash('Cannot reset password for admin account.', 'danger')
        return redirect(url_for('admin_dashboard'))

    new_password = f"Exam{random.randint(100000, 999999)}"
    user.password = generate_password_hash(new_password)
    user.plain_password = new_password
    db.session.commit()

    flash(f'Password reset for "{user.username}". New password: {new_password}', 'success')
    return redirect(url_for('admin_dashboard'))

@app.route('/admin/set-user-password/<int:user_id>', methods=['POST'])
@login_required
def admin_set_user_password(user_id):
    if current_user.role != 'admin':
        return redirect(url_for('student_dashboard'))

    user = User.query.get_or_404(user_id)
    if user.role == 'admin':
        flash('Cannot set password for admin account from here.', 'danger')
        return redirect(url_for('admin_dashboard'))

    new_password = (request.form.get('new_password') or '').strip()
    if len(new_password) < 4:
        flash('Password must be at least 4 characters.', 'danger')
        return redirect(url_for('admin_dashboard'))

    user.password = generate_password_hash(new_password)
    user.plain_password = new_password
    db.session.commit()

    flash(f'Password updated for "{user.username}".', 'success')
    return redirect(url_for('admin_dashboard'))


@app.route('/admin/delete-exam/<int:exam_id>')
@login_required
def admin_delete_exam(exam_id):
    if current_user.role != 'admin':
        return redirect(url_for('student_dashboard'))
    
    exam = Exam.query.get_or_404(exam_id)
    title = exam.title
    
    for qset in exam.question_sets:
        Question.query.filter_by(set_id=qset.id).delete()
    QuestionSet.query.filter_by(exam_id=exam_id).delete()
    Question.query.filter_by(exam_id=exam_id, set_id=None).delete()
    CheatingLog.query.filter_by(exam_id=exam_id).delete()
    Result.query.filter_by(exam_id=exam_id).delete()
    db.session.delete(exam)
    db.session.commit()
    
    flash(f'Exam "{title}" has been deleted.', 'success')
    return redirect(url_for('admin_dashboard'))


# ========== INITIAL SETUP ==========

with app.app_context():
    try:
        # Add new columns if not exists
        with db.engine.connect() as conn:
            try:
                conn.execute(db.text("ALTER TABLE users ADD COLUMN enrollment_number VARCHAR(50)"))
                conn.commit()
                print("✅ Added enrollment_number column")
            except Exception:
                pass
            try:
                conn.execute(db.text("ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT TRUE"))
                conn.commit()
                print("✅ Added is_active column")
            except Exception:
                pass
            try:
                conn.execute(db.text("ALTER TABLE users ADD COLUMN plain_password VARCHAR(200)"))
                conn.commit()
                print("✅ Added plain_password column")
            except Exception:
                pass
            try:
                conn.execute(db.text("ALTER TABLE exams ADD COLUMN exam_type VARCHAR(20) DEFAULT 'normal'"))
                conn.commit()
                print("✅ Added exam_type column")
            except Exception:
                pass
            try:
                conn.execute(db.text("ALTER TABLE exams ADD COLUMN number_of_sets INT DEFAULT 0"))
                conn.commit()
                print("✅ Added number_of_sets column")
            except Exception:
                pass
            try:
                conn.execute(db.text("ALTER TABLE questions ADD COLUMN set_id INT"))
                conn.commit()
                print("✅ Added set_id column to questions")
            except Exception:
                pass
            try:
                conn.execute(db.text("ALTER TABLE results ADD COLUMN set_id INT"))
                conn.commit()
                print("✅ Added set_id column to results")
            except Exception:
                pass
        
        db.create_all()
        
        # Create admin account if not exists
        if not User.query.filter_by(username='admin').first():
            admin = User(
                username='admin',
                email='admin@marwadiuniversity.edu.in',
                password=generate_password_hash('admin123'),
                plain_password='admin123',
                role='admin',
                full_name='System Admin',
                is_active=True
            )
            db.session.add(admin)
            db.session.commit()
            print("✅ Admin account created: admin / admin123")
        
        # Create teacher account if not exists
        if not User.query.filter_by(username='teacher').first():
            teacher = User(
                username='teacher',
                email='teacher@marwadiuniversity.edu.in',
                password=generate_password_hash('teacher123'),
                plain_password='teacher123',
                role='teacher',
                full_name='Default Teacher',
                is_active=True
            )
            db.session.add(teacher)
            db.session.commit()
            print("✅ Teacher account created: teacher / teacher123")
        
        print("\n" + "="*60)
        print("🎉 EXAMVISION READY!")
        print("📧 Student email: @marwadiuniversity.ac.in")
        print("📧 Teacher/Faculty email: @marwadiuniversity.edu.in or @marwadieducation.edu.in")
        print("👤 Admin Login: admin / admin123")
        print("👤 Teacher Login: teacher / teacher123")
        print("🌐 URL: http://localhost:5000")
        print("="*60 + "\n")
    except Exception as e:
        print(f"❌ DB Error: {e}")

if __name__ == '__main__':
    app.run(debug=True, host='127.0.0.1', port=5000)