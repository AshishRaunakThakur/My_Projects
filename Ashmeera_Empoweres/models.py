from datetime import datetime
from flask_login import UserMixin
from flask_sqlalchemy import SQLAlchemy
from werkzeug.security import generate_password_hash, check_password_hash

db = SQLAlchemy()

class User(UserMixin, db.Model):
    __tablename__ = 'users'
    
    id = db.Column(db.Integer, primary_key=True)
    email = db.Column(db.String(100), unique=True, nullable=False)
    password_hash = db.Column(db.String(255), nullable=False)
    full_name = db.Column(db.String(100), nullable=False)
    phone = db.Column(db.String(20))
    user_type = db.Column(db.String(20), default='user')
    is_active = db.Column(db.Boolean, default=True)
    is_verified = db.Column(db.Boolean, default=False)
    profile_photo = db.Column(db.String(255))
    resume = db.Column(db.String(255))
    bio = db.Column(db.Text)
    location = db.Column(db.String(100))
    disability_type = db.Column(db.String(50))
    date_of_birth = db.Column(db.String(20))
    gender = db.Column(db.String(20))
    preferred_job_type = db.Column(db.String(50))
    preferred_location = db.Column(db.String(100))
    expected_salary_min = db.Column(db.Integer)
    expected_salary_max = db.Column(db.Integer)
    work_from_home = db.Column(db.Boolean, default=False)
    skills = db.Column(db.Text)
    
    # Security Question Fields
    security_question = db.Column(db.String(200))
    security_answer = db.Column(db.String(200))
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    last_login = db.Column(db.DateTime)
    
    # Relationships
    applications = db.relationship('Application', backref='applicant', lazy=True)
    notifications = db.relationship('Notification', backref='recipient', lazy=True)
    saved_jobs = db.relationship('SavedJob', backref='saver', lazy=True)
    
    def set_password(self, password):
        self.password_hash = generate_password_hash(password)
    
    def check_password(self, password):
        return check_password_hash(self.password_hash, password)
    
    @property
    def profile_completion(self):
        fields = [self.profile_photo, self.bio, self.location, self.skills, self.resume]
        completed = sum(1 for f in fields if f)
        return int((completed / len(fields)) * 100)
    
    def __repr__(self):
        return f'<User {self.email}>'

class Employer(db.Model):
    __tablename__ = 'employers'
    
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    company_name = db.Column(db.String(100), nullable=False)
    company_logo = db.Column(db.String(255))
    company_description = db.Column(db.Text)
    company_website = db.Column(db.String(100))
    company_size = db.Column(db.String(50))
    industry = db.Column(db.String(50))
    founded_year = db.Column(db.Integer)
    headquarters = db.Column(db.String(100))
    is_verified = db.Column(db.Boolean, default=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    user = db.relationship('User', backref='employer_profile', lazy=True)
    
    def __repr__(self):
        return f'<Employer {self.company_name}>'

class Job(db.Model):
    __tablename__ = 'jobs'
    
    id = db.Column(db.Integer, primary_key=True)
    employer_id = db.Column(db.Integer, db.ForeignKey('employers.id'), nullable=False)
    title = db.Column(db.String(100), nullable=False)
    description = db.Column(db.Text, nullable=False)
    requirements = db.Column(db.Text)
    category = db.Column(db.String(50))
    job_type = db.Column(db.String(50))
    experience_level = db.Column(db.String(50))
    location = db.Column(db.String(100))
    salary_min = db.Column(db.Integer)
    salary_max = db.Column(db.Integer)
    work_from_home = db.Column(db.Boolean, default=False)
    disability_friendly = db.Column(db.Boolean, default=True)
    is_active = db.Column(db.Boolean, default=True)
    is_featured = db.Column(db.Boolean, default=False)
    posted_date = db.Column(db.DateTime, default=datetime.utcnow)
    expiry_date = db.Column(db.DateTime)
    
    employer = db.relationship('Employer', backref='jobs', lazy=True)
    
    @property
    def applicant_count(self):
        return Application.query.filter_by(job_id=self.id).count()
    
    @property
    def days_active(self):
        if self.posted_date:
            delta = datetime.utcnow() - self.posted_date
            return delta.days
        return 0
    
    def __repr__(self):
        return f'<Job {self.title}>'

class Application(db.Model):
    __tablename__ = 'applications'
    
    id = db.Column(db.Integer, primary_key=True)
    job_id = db.Column(db.Integer, db.ForeignKey('jobs.id'), nullable=False)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    status = db.Column(db.String(20), default='pending')
    cover_letter = db.Column(db.Text)
    applied_date = db.Column(db.DateTime, default=datetime.utcnow)
    updated_date = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    match_percentage = db.Column(db.Integer)
    
    # Relationships
    job = db.relationship('Job', backref='applications', lazy=True)
    
    __table_args__ = (
        db.UniqueConstraint('job_id', 'user_id', name='unique_application'),
    )

class SavedJob(db.Model):
    __tablename__ = 'saved_jobs'
    
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    job_id = db.Column(db.Integer, db.ForeignKey('jobs.id'), nullable=False)
    saved_date = db.Column(db.DateTime, default=datetime.utcnow)
    
    __table_args__ = (
        db.UniqueConstraint('user_id', 'job_id', name='unique_saved_job'),
    )

class Notification(db.Model):
    __tablename__ = 'notifications'
    
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    title = db.Column(db.String(100), nullable=False)
    message = db.Column(db.Text, nullable=False)
    type = db.Column(db.String(20), default='info')
    is_read = db.Column(db.Boolean, default=False)
    link = db.Column(db.String(255))
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

class Skill(db.Model):
    __tablename__ = 'skills'
    
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    skill_name = db.Column(db.String(50), nullable=False)
    proficiency = db.Column(db.String(20))
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

class Education(db.Model):
    __tablename__ = 'education'
    
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    degree = db.Column(db.String(100), nullable=False)
    institution = db.Column(db.String(100), nullable=False)
    field_of_study = db.Column(db.String(100))
    start_date = db.Column(db.String(20))
    end_date = db.Column(db.String(20))
    grade = db.Column(db.String(20))
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

class Certificate(db.Model):
    __tablename__ = 'certificates'
    
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    certificate_name = db.Column(db.String(100), nullable=False)
    issuing_organization = db.Column(db.String(100))
    issue_date = db.Column(db.String(20))
    expiry_date = db.Column(db.String(20))
    credential_id = db.Column(db.String(100))
    certificate_file = db.Column(db.String(255))
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

class Feedback(db.Model):
    __tablename__ = 'feedbacks'
    
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    rating = db.Column(db.Integer)
    subject = db.Column(db.String(100))
    message = db.Column(db.Text)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

class Report(db.Model):
    __tablename__ = 'reports'
    
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'))
    job_id = db.Column(db.Integer, db.ForeignKey('jobs.id'))
    report_type = db.Column(db.String(50))
    description = db.Column(db.Text)
    status = db.Column(db.String(20), default='pending')
    created_at = db.Column(db.DateTime, default=datetime.utcnow)