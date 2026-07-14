# app.py
from flask import Flask, render_template, request, redirect, url_for, flash, session, jsonify
from datetime import datetime, timedelta
import sqlite3
import os
import random
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from contextlib import contextmanager

app = Flask(__name__)
app.secret_key = 'your_secret_key_here_12345'
app.permanent_session_lifetime = timedelta(minutes=60)

# Email Configuration
EMAIL_CONFIG = {
    'SMTP_SERVER': 'smtp.gmail.com',
    'SMTP_PORT': 587,
    'SENDER_EMAIL': 'studyplannerofficial386@gmail.com',
    'SENDER_PASSWORD': 'nzhjmuxrnabhqeim'
}

otp_storage = {}

@contextmanager
def get_db():
    conn = sqlite3.connect('study_planner.db', timeout=10)
    conn.row_factory = sqlite3.Row
    try:
        yield conn
        conn.commit()
    except Exception as e:
        conn.rollback()
        raise e
    finally:
        conn.close()

def init_db():
    with get_db() as conn:
        c = conn.cursor()
        
        c.execute('''CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            role TEXT DEFAULT 'user',
            created_at TEXT,
            total_study_time REAL DEFAULT 0,
            full_name TEXT DEFAULT '',
            university TEXT DEFAULT '',
            current_semester INTEGER DEFAULT 1,
            profile_updated INTEGER DEFAULT 0,
            verified INTEGER DEFAULT 0
        )''')
        
        c.execute('''CREATE TABLE IF NOT EXISTS subjects (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            subject_name TEXT NOT NULL,
            target_hours REAL DEFAULT 100,
            completed_hours REAL DEFAULT 0,
            created_at TEXT,
            color TEXT,
            semester INTEGER DEFAULT 1,
            FOREIGN KEY (user_id) REFERENCES users (id)
        )''')
        
        c.execute('''CREATE TABLE IF NOT EXISTS study_sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            subject_id INTEGER NOT NULL,
            duration REAL NOT NULL,
            date TEXT NOT NULL,
            created_at TEXT,
            semester INTEGER DEFAULT 1,
            FOREIGN KEY (user_id) REFERENCES users (id),
            FOREIGN KEY (subject_id) REFERENCES subjects (id)
        )''')
        
        c.execute('''CREATE TABLE IF NOT EXISTS monthly_stats (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            month TEXT NOT NULL,
            year INTEGER NOT NULL,
            total_hours REAL DEFAULT 0,
            semester INTEGER DEFAULT 1,
            FOREIGN KEY (user_id) REFERENCES users (id)
        )''')
        
        c.execute('''CREATE TABLE IF NOT EXISTS semesters (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            semester_number INTEGER NOT NULL,
            start_date TEXT,
            end_date TEXT,
            total_hours REAL DEFAULT 0,
            status TEXT DEFAULT 'active',
            FOREIGN KEY (user_id) REFERENCES users (id)
        )''')
        
        c.execute('''CREATE TABLE IF NOT EXISTS study_streak (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            streak_date TEXT NOT NULL,
            UNIQUE(user_id, streak_date),
            FOREIGN KEY (user_id) REFERENCES users (id)
        )''')
        
        c.execute('''CREATE TABLE IF NOT EXISTS achievements (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            achievement_name TEXT NOT NULL,
            achieved_date TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users (id)
        )''')
        
        c.execute('''CREATE TABLE IF NOT EXISTS study_notes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            subject_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            content TEXT,
            created_at TEXT,
            FOREIGN KEY (user_id) REFERENCES users (id),
            FOREIGN KEY (subject_id) REFERENCES subjects (id)
        )''')
        
        c.execute("PRAGMA table_info(users)")
        user_cols = [row[1] for row in c.fetchall()]
        if 'verified' not in user_cols:
            c.execute("ALTER TABLE users ADD COLUMN verified INTEGER DEFAULT 0")
        
        c.execute("UPDATE users SET verified = 1 WHERE profile_updated = 1")
        
        c.execute("SELECT * FROM users WHERE username = 'Ashish'")
        if not c.fetchone():
            c.execute('''INSERT INTO users 
                (username, password, email, role, created_at, full_name, university, current_semester, profile_updated, total_study_time, verified) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)''',
                ('Ashish', '0386', 'ashishraunksmp@gmail.com', 'admin', 
                 datetime.now().strftime("%Y-%m-%d %H:%M:%S"), 
                 'Ashish Raunak', 'Marwadi University', 1, 1, 0, 1))
            
            admin_id = c.lastrowid
            c.execute('''INSERT INTO semesters 
                (user_id, semester_number, start_date, status) 
                VALUES (?, ?, ?, ?)''',
                (admin_id, 1, datetime.now().strftime("%Y-%m-%d"), 'active'))
    
    print("✅ Database initialized successfully!")

init_db()

def generate_otp():
    return str(random.randint(100000, 999999))

def send_otp_email(recipient_email, otp):
    try:
        msg = MIMEMultipart()
        msg['From'] = EMAIL_CONFIG['SENDER_EMAIL']
        msg['To'] = recipient_email
        msg['Subject'] = 'Study Planner - Password Reset OTP'
        
        body = f"""
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body {{ font-family: 'Segoe UI', Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 0; padding: 40px 20px; }}
                .container {{ max-width: 500px; margin: 0 auto; background: white; border-radius: 20px; padding: 40px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }}
                .header {{ text-align: center; margin-bottom: 30px; }}
                .header h1 {{ color: #667eea; font-size: 32px; margin: 10px 0; }}
                .otp-box {{ background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 15px; text-align: center; margin: 30px 0; }}
                .otp-code {{ color: white; font-size: 48px; font-weight: 800; letter-spacing: 8px; font-family: 'Courier New', monospace; }}
                .warning {{ background: #fff3cd; color: #856404; padding: 15px; border-radius: 10px; text-align: center; margin: 20px 0; }}
                .footer {{ text-align: center; color: #999; font-size: 14px; margin-top: 30px; }}
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>📚 Study Planner</h1>
                </div>
                <div class="otp-box">
                    <div class="otp-code">{otp}</div>
                </div>
                <div class="warning">⏰ This OTP is valid for 10 minutes only.</div>
                <div class="footer">© 2026 Study Planner</div>
            </div>
        </body>
        </html>
        """
        
        msg.attach(MIMEText(body, 'html'))
        server = smtplib.SMTP(EMAIL_CONFIG['SMTP_SERVER'], EMAIL_CONFIG['SMTP_PORT'])
        server.starttls()
        server.login(EMAIL_CONFIG['SENDER_EMAIL'], EMAIL_CONFIG['SENDER_PASSWORD'])
        server.send_message(msg)
        server.quit()
        return True
    except Exception as e:
        print(f"Email error: {e}")
        return False

def get_user_by_email(email):
    with get_db() as conn:
        c = conn.cursor()
        c.execute("SELECT * FROM users WHERE email = ?", (email,))
        user = c.fetchone()
        return dict(user) if user else None

def update_password(email, new_password):
    email = (email or "").strip()
    if not email:
        return False
    with get_db() as conn:
        c = conn.cursor()
        c.execute("UPDATE users SET password = ? WHERE LOWER(TRIM(email)) = LOWER(?)", (new_password, email))
        return c.rowcount > 0

def authenticate_user(username, password):
    with get_db() as conn:
        c = conn.cursor()
        c.execute("SELECT * FROM users WHERE username = ? AND password = ?", (username, password))
        user = c.fetchone()
        return dict(user) if user else None

def get_user(user_id):
    with get_db() as conn:
        c = conn.cursor()
        c.execute("SELECT * FROM users WHERE id = ?", (user_id,))
        user = c.fetchone()
        if user:
            return dict(user)
        return None

def update_user_profile(user_id, full_name, university, current_semester):
    with get_db() as conn:
        c = conn.cursor()
        c.execute("""UPDATE users 
                     SET full_name = ?, university = ?, current_semester = ?, profile_updated = 1, verified = 1 
                     WHERE id = ?""",
                  (full_name, university, current_semester, user_id))

def create_user(username, email, password, role='user'):
    try:
        with get_db() as conn:
            c = conn.cursor()
            c.execute("""INSERT INTO users 
                         (username, email, password, role, created_at, profile_updated, current_semester, total_study_time, full_name, university, verified) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)""",
                      (username, email, password, role, datetime.now().strftime("%Y-%m-%d %H:%M:%S"), 0, 1, 0, '', '', 0))
            user_id = c.lastrowid
            c.execute("INSERT INTO semesters (user_id, semester_number, start_date, status) VALUES (?, ?, ?, ?)",
                      (user_id, 1, datetime.now().strftime("%Y-%m-%d"), 'active'))
            return user_id
    except sqlite3.IntegrityError:
        return None

def add_subject(user_id, subject_name, target_hours=100, semester=1):
    colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#FF9F4A', '#9B59B6', '#3498DB']
    color = random.choice(colors)
    
    with get_db() as conn:
        c = conn.cursor()
        c.execute("""INSERT INTO subjects 
                     (user_id, subject_name, target_hours, completed_hours, created_at, color, semester) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)""",
                  (user_id, subject_name, target_hours, 0, 
                   datetime.now().strftime("%Y-%m-%d %H:%M:%S"), color, semester))
        return c.lastrowid

def get_user_subjects(user_id, semester=None):
    with get_db() as conn:
        c = conn.cursor()
        if semester:
            c.execute("SELECT * FROM subjects WHERE user_id = ? AND semester = ? ORDER BY created_at DESC", (user_id, semester))
        else:
            c.execute("SELECT * FROM subjects WHERE user_id = ? ORDER BY created_at DESC", (user_id,))
        subjects = c.fetchall()
        return [dict(subject) for subject in subjects]

def delete_subject(subject_id, user_id):
    with get_db() as conn:
        c = conn.cursor()
        c.execute("SELECT duration FROM study_sessions WHERE subject_id = ? AND user_id = ?", 
                  (subject_id, user_id))
        sessions = c.fetchall()
        total_duration = sum(session['duration'] for session in sessions)
        c.execute("UPDATE users SET total_study_time = total_study_time - ? WHERE id = ?",
                  (total_duration, user_id))
        c.execute("DELETE FROM study_sessions WHERE subject_id = ? AND user_id = ?", 
                  (subject_id, user_id))
        c.execute("DELETE FROM subjects WHERE id = ? AND user_id = ?", (subject_id, user_id))
        return True

def update_study_streak(user_id):
    today = datetime.now().strftime("%Y-%m-%d")
    with get_db() as conn:
        c = conn.cursor()
        c.execute("SELECT * FROM study_streak WHERE user_id = ? AND streak_date = ?", (user_id, today))
        if not c.fetchone():
            c.execute("INSERT INTO study_streak (user_id, streak_date) VALUES (?, ?)", (user_id, today))

def check_achievements(user_id, total_hours):
    achievements = {
        10: "🥉 Bronze Scholar",
        50: "🥈 Silver Scholar", 
        100: "🥇 Gold Scholar",
        200: "💎 Platinum Scholar",
        500: "👑 Grand Master"
    }
    
    awarded = []
    with get_db() as conn:
        c = conn.cursor()
        for hours, name in achievements.items():
            if total_hours >= hours:
                c.execute("SELECT * FROM achievements WHERE user_id = ? AND achievement_name = ?", (user_id, name))
                if not c.fetchone():
                    c.execute("INSERT INTO achievements (user_id, achievement_name, achieved_date) VALUES (?, ?, ?)",
                             (user_id, name, datetime.now().strftime("%Y-%m-%d %H:%M:%S")))
                    awarded.append(name)
    return awarded

def add_study_session(user_id, subject_id, duration, date, semester=1):
    with get_db() as conn:
        c = conn.cursor()
        
        c.execute("""INSERT INTO study_sessions (user_id, subject_id, duration, date, created_at, semester) 
                     VALUES (?, ?, ?, ?, ?, ?)""",
                  (user_id, subject_id, duration, date, 
                   datetime.now().strftime("%Y-%m-%d %H:%M:%S"), semester))
        
        c.execute("UPDATE subjects SET completed_hours = completed_hours + ? WHERE id = ?",
                  (duration, subject_id))
        c.execute("UPDATE users SET total_study_time = total_study_time + ? WHERE id = ?",
                  (duration, user_id))
        
        month = datetime.strptime(date, "%Y-%m-%d").strftime("%B")
        year = datetime.strptime(date, "%Y-%m-%d").year
        c.execute("SELECT * FROM monthly_stats WHERE user_id = ? AND month = ? AND year = ?", (user_id, month, year))
        stat = c.fetchone()
        if stat:
            c.execute("UPDATE monthly_stats SET total_hours = total_hours + ? WHERE id = ?", (duration, stat['id']))
        else:
            c.execute("INSERT INTO monthly_stats (user_id, month, year, total_hours, semester) VALUES (?, ?, ?, ?, ?)",
                      (user_id, month, year, duration, semester))
        
        c.execute("UPDATE semesters SET total_hours = total_hours + ? WHERE user_id = ? AND semester_number = ? AND status = 'active'",
                  (duration, user_id, semester))
        
        return c.lastrowid

def get_user_sessions(user_id, semester=None):
    with get_db() as conn:
        c = conn.cursor()
        if semester:
            c.execute("""SELECT ss.*, s.subject_name 
                         FROM study_sessions ss
                         JOIN subjects s ON ss.subject_id = s.id
                         WHERE ss.user_id = ? AND ss.semester = ?
                         ORDER BY ss.date DESC LIMIT 50""", (user_id, semester))
        else:
            c.execute("""SELECT ss.*, s.subject_name 
                         FROM study_sessions ss
                         JOIN subjects s ON ss.subject_id = s.id
                         WHERE ss.user_id = ?
                         ORDER BY ss.date DESC LIMIT 50""", (user_id,))
        sessions = c.fetchall()
        return [dict(session) for session in sessions]

def delete_session(session_id, user_id):
    with get_db() as conn:
        c = conn.cursor()
        c.execute("SELECT * FROM study_sessions WHERE id = ? AND user_id = ?", 
                  (session_id, user_id))
        session = c.fetchone()
        
        if session:
            c.execute("UPDATE subjects SET completed_hours = completed_hours - ? WHERE id = ?",
                      (session['duration'], session['subject_id']))
            c.execute("UPDATE users SET total_study_time = total_study_time - ? WHERE id = ?",
                      (session['duration'], user_id))
            c.execute("DELETE FROM study_sessions WHERE id = ?", (session_id,))
            
            month = datetime.strptime(session['date'], "%Y-%m-%d").strftime("%B")
            year = datetime.strptime(session['date'], "%Y-%m-%d").year
            c.execute("UPDATE monthly_stats SET total_hours = total_hours - ? WHERE user_id = ? AND month = ? AND year = ?",
                      (session['duration'], user_id, month, year))
            
            c.execute("UPDATE semesters SET total_hours = total_hours - ? WHERE user_id = ? AND semester_number = ?",
                      (session['duration'], user_id, session['semester']))
            return True
        return False

def end_semester(user_id, current_semester):
    with get_db() as conn:
        c = conn.cursor()
        c.execute("UPDATE semesters SET status = 'completed', end_date = ? WHERE user_id = ? AND semester_number = ?",
                  (datetime.now().strftime("%Y-%m-%d"), user_id, current_semester))
        new_semester = current_semester + 1
        c.execute("INSERT INTO semesters (user_id, semester_number, start_date, status) VALUES (?, ?, ?, ?)",
                  (user_id, new_semester, datetime.now().strftime("%Y-%m-%d"), 'active'))
        c.execute("UPDATE users SET current_semester = ? WHERE id = ?", (new_semester, user_id))
        return new_semester

def sync_semester_records_on_profile_change(user_id, old_sem, new_sem):
    """When current semester is changed in profile, mark skipped semesters completed and align DB rows so graphs stay correct."""
    try:
        old_sem = int(old_sem)
        new_sem = int(new_sem)
    except (TypeError, ValueError):
        return None
    if old_sem == new_sem:
        return None
    today = datetime.now().strftime("%Y-%m-%d")
    with get_db() as conn:
        c = conn.cursor()

        def mark_semester_completed(n):
            c.execute(
                "SELECT id FROM semesters WHERE user_id = ? AND semester_number = ? ORDER BY id DESC LIMIT 1",
                (user_id, n),
            )
            row = c.fetchone()
            if row:
                c.execute(
                    """UPDATE semesters SET status = 'completed',
                       end_date = CASE WHEN end_date IS NULL OR TRIM(COALESCE(end_date, '')) = '' THEN ? ELSE end_date END
                       WHERE id = ?""",
                    (today, row["id"]),
                )
            else:
                c.execute(
                    """INSERT INTO semesters (user_id, semester_number, start_date, end_date, status, total_hours)
                       VALUES (?, ?, ?, ?, 'completed', 0)""",
                    (user_id, n, today, today),
                )

        def ensure_semester_active(n):
            c.execute(
                "SELECT id FROM semesters WHERE user_id = ? AND semester_number = ? ORDER BY id DESC LIMIT 1",
                (user_id, n),
            )
            row = c.fetchone()
            if row:
                c.execute(
                    "UPDATE semesters SET status = 'active', end_date = NULL WHERE id = ?",
                    (row["id"],),
                )
            else:
                c.execute(
                    "INSERT INTO semesters (user_id, semester_number, start_date, status, total_hours) VALUES (?, ?, ?, 'active', 0)",
                    (user_id, n, today),
                )

        if new_sem > old_sem:
            for n in range(old_sem, new_sem):
                mark_semester_completed(n)
            ensure_semester_active(new_sem)
        else:
            for n in range(new_sem + 1, old_sem + 1):
                mark_semester_completed(n)
            ensure_semester_active(new_sem)

        c.execute(
            """UPDATE semesters SET status = 'completed',
               end_date = CASE WHEN end_date IS NULL OR TRIM(COALESCE(end_date, '')) = '' THEN ? ELSE end_date END
               WHERE user_id = ? AND semester_number != ? AND status = 'active'""",
            (today, user_id, new_sem),
        )
    return new_sem

def get_monthly_data(user_id, semester=None):
    with get_db() as conn:
        c = conn.cursor()
        if semester:
            c.execute("SELECT month, year, total_hours FROM monthly_stats WHERE user_id = ? AND semester = ? ORDER BY year, month", 
                      (user_id, semester))
        else:
            c.execute("SELECT month, year, total_hours FROM monthly_stats WHERE user_id = ? ORDER BY year, month", (user_id,))
        data = c.fetchall()
        return [dict(d) for d in data]

def get_semester_data(user_id):
    with get_db() as conn:
        c = conn.cursor()
        c.execute(
            """SELECT semester_number,
                      SUM(total_hours) AS total_hours,
                      MIN(start_date) AS start_date,
                      MAX(end_date) AS end_date,
                      CASE WHEN SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) > 0 THEN 'active' ELSE 'completed' END AS status
               FROM semesters WHERE user_id = ?
               GROUP BY semester_number
               ORDER BY semester_number""",
            (user_id,),
        )
        data = c.fetchall()
        return [dict(d) for d in data]

def get_progress(user_id, semester=None):
    subjects = get_user_subjects(user_id, semester)
    progress_data = []
    
    for subject in subjects:
        percentage = (subject['completed_hours'] / subject['target_hours'] * 100) if subject['target_hours'] > 0 else 0
        progress_data.append({
            'subject_id': subject['id'],
            'subject_name': subject['subject_name'],
            'completed': subject['completed_hours'],
            'target': subject['target_hours'],
            'percentage': round(percentage, 1),
            'color': subject.get('color', '#6366f1')
        })
    return progress_data

def get_study_statistics(user_id, semester=None):
    user = get_user(user_id)
    sessions = get_user_sessions(user_id, semester)
    
    total_sessions = len(sessions)
    avg_duration = sum(s['duration'] for s in sessions) / total_sessions if total_sessions > 0 else 0
    today = datetime.now().strftime("%Y-%m-%d")
    
    today_sessions = [s for s in sessions if s['date'] == today]
    today_hours = sum(s['duration'] for s in today_sessions)
    
    weekly_hours = []
    for i in range(7):
        date = (datetime.now() - timedelta(days=i)).strftime("%Y-%m-%d")
        day_hours = sum(s['duration'] for s in sessions if s['date'] == date)
        weekly_hours.append(day_hours)
    weekly_hours.reverse()
    
    return {
        'total_study_time': user['total_study_time'] if user else 0,
        'total_sessions': total_sessions,
        'avg_session_duration': round(avg_duration, 1) if total_sessions > 0 else 0,
        'today_hours': round(today_hours, 1),
        'today_sessions': len(today_sessions),
        'weekly_hours': weekly_hours
    }

def get_study_streak(user_id):
    with get_db() as conn:
        c = conn.cursor()
        c.execute("SELECT streak_date FROM study_streak WHERE user_id = ? ORDER BY streak_date DESC", (user_id,))
        dates = [row['streak_date'] for row in c.fetchall()]
    
    if not dates:
        return 0
    
    streak = 1
    current = datetime.strptime(dates[0], "%Y-%m-%d").date()
    
    for i in range(1, len(dates)):
        prev = datetime.strptime(dates[i], "%Y-%m-%d").date()
        diff = (current - prev).days
        if diff == 1:
            streak += 1
            current = prev
        elif diff > 1:
            break
        else:
            current = prev
    return streak

def get_user_achievements(user_id):
    with get_db() as conn:
        c = conn.cursor()
        c.execute("SELECT achievement_name, achieved_date FROM achievements WHERE user_id = ? ORDER BY achieved_date", (user_id,))
        achievements = c.fetchall()
        return [dict(a) for a in achievements]

def add_study_note(user_id, subject_id, title, content):
    with get_db() as conn:
        c = conn.cursor()
        c.execute("INSERT INTO study_notes (user_id, subject_id, title, content, created_at) VALUES (?, ?, ?, ?, ?)",
                  (user_id, subject_id, title, content, datetime.now().strftime("%Y-%m-%d %H:%M:%S")))
        return c.lastrowid

def get_study_notes(user_id, subject_id=None):
    with get_db() as conn:
        c = conn.cursor()
        if subject_id:
            c.execute("SELECT n.*, s.subject_name FROM study_notes n JOIN subjects s ON n.subject_id = s.id WHERE n.user_id = ? AND n.subject_id = ? ORDER BY n.created_at DESC", 
                      (user_id, subject_id))
        else:
            c.execute("SELECT n.*, s.subject_name FROM study_notes n JOIN subjects s ON n.subject_id = s.id WHERE n.user_id = ? ORDER BY n.created_at DESC", (user_id,))
        notes = c.fetchall()
        return [dict(note) for note in notes]

def delete_study_note(note_id, user_id):
    with get_db() as conn:
        c = conn.cursor()
        c.execute("DELETE FROM study_notes WHERE id = ? AND user_id = ?", (note_id, user_id))

def get_study_note(note_id, user_id):
    with get_db() as conn:
        c = conn.cursor()
        c.execute(
            """SELECT n.*, s.subject_name FROM study_notes n
               JOIN subjects s ON n.subject_id = s.id
               WHERE n.id = ? AND n.user_id = ?""",
            (note_id, user_id),
        )
        row = c.fetchone()
        return dict(row) if row else None

def update_study_note(note_id, user_id, subject_id, title, content):
    with get_db() as conn:
        c = conn.cursor()
        c.execute(
            """UPDATE study_notes SET subject_id = ?, title = ?, content = ?
               WHERE id = ? AND user_id = ?""",
            (subject_id, title, content or "", note_id, user_id),
        )
        return c.rowcount > 0

def get_weekly_report(user_id):
    weekly_data = []
    for i in range(7):
        date = (datetime.now() - timedelta(days=i)).strftime("%Y-%m-%d")
        with get_db() as conn:
            c = conn.cursor()
            c.execute("SELECT SUM(duration) as total FROM study_sessions WHERE user_id = ? AND date = ?", (user_id, date))
            result = c.fetchone()
            hours = result['total'] if result and result['total'] else 0
            weekly_data.append({'date': date, 'hours': round(hours, 1)})
    return weekly_data[::-1]

def get_monthly_report(user_id):
    current_month = datetime.now().strftime("%m")
    current_year = datetime.now().year
    
    with get_db() as conn:
        c = conn.cursor()
        c.execute("""SELECT date, SUM(duration) as total FROM study_sessions 
                     WHERE user_id = ? AND strftime('%m', date) = ? AND strftime('%Y', date) = ?
                     GROUP BY date ORDER BY date""", 
                  (user_id, current_month, str(current_year)))
        data = c.fetchall()
        return [{'date': d['date'], 'hours': round(d['total'], 1)} for d in data]

# Leaderboard function
def get_leaderboard():
    with get_db() as conn:
        c = conn.cursor()
        c.execute("""SELECT id, username, total_study_time 
                     FROM users 
                     WHERE role != 'admin'
                     ORDER BY total_study_time DESC 
                     LIMIT 20""")
        users = c.fetchall()
        return [dict(u) for u in users]

# ============= ROUTES =============

@app.route('/')
def index():
    if 'user_id' in session:
        return redirect(url_for('dashboard'))
    return render_template('index.html')

@app.route('/register', methods=['GET', 'POST'])
def register():
    if request.method == 'POST':
        username = request.form['username']
        email = request.form['email']
        password = request.form['password']
        confirm_password = request.form['confirm_password']
        
        if password != confirm_password:
            flash('Passwords do not match! ❌', 'error')
            return redirect(url_for('register'))
        
        user_id = create_user(username, email, password, 'user')
        
        if user_id:
            flash('Registration successful! Please login. 🎉', 'success')
            return redirect(url_for('login'))
        else:
            flash('Username or email already exists! ❌', 'error')
    
    return render_template('register.html')

@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        username = request.form['username']
        password = request.form['password']
        
        user = authenticate_user(username, password)
        
        if user:
            session['user_id'] = user['id']
            session['username'] = user['username']
            session['role'] = user['role']
            session.permanent = True
            flash(f'Welcome back, {username}! 🎉', 'success')
            return redirect(url_for('dashboard'))
        else:
            flash('Invalid username or password! ❌', 'error')
    
    return render_template('login.html')

@app.route('/forgot_password', methods=['GET', 'POST'])
def forgot_password():
    if request.method == 'POST':
        email = request.form['email']
        user = get_user_by_email(email)
        
        if user:
            otp = generate_otp()
            otp_storage[email] = {
                'otp': otp,
                'expiry': datetime.now() + timedelta(minutes=10)
            }
            if send_otp_email(email, otp):
                flash(f'6-digit OTP sent to {email}! 📧', 'success')
                return redirect(url_for('verify_otp', email=email))
            else:
                flash('Failed to send email! Please try again. ❌', 'error')
        else:
            flash('Email not registered! ❌', 'error')
    
    return render_template('forgot_password.html')

@app.route('/verify_otp/<email>', methods=['GET', 'POST'])
def verify_otp(email):
    if request.method == 'POST':
        entered_otp = request.form['otp']
        
        if email in otp_storage:
            data = otp_storage[email]
            if datetime.now() <= data['expiry']:
                if data['otp'] == entered_otp:
                    session['reset_email'] = email
                    flash('OTP verified! Set new password. ✅', 'success')
                    return redirect(url_for('reset_password'))
                else:
                    flash('Invalid OTP! ❌', 'error')
            else:
                flash('OTP expired! Request again. ⏰', 'error')
                del otp_storage[email]
                return redirect(url_for('forgot_password'))
        else:
            flash('No OTP request found! ❌', 'error')
            return redirect(url_for('forgot_password'))
    
    return render_template('verify_otp.html', email=email)

@app.route('/reset_password', methods=['GET', 'POST'])
def reset_password():
    if 'reset_email' not in session:
        flash('Session expired! Start again. ❌', 'error')
        return redirect(url_for('forgot_password'))
    
    if request.method == 'POST':
        new_password = request.form['new_password']
        confirm_password = request.form['confirm_password']
        
        if new_password != confirm_password:
            flash('Passwords do not match! ❌', 'error')
            return redirect(url_for('reset_password'))
        
        if len(new_password) < 4:
            flash('Password must be at least 4 characters! ❌', 'error')
            return redirect(url_for('reset_password'))
        
        if update_password(session['reset_email'], new_password):
            flash('Password updated successfully! Login now. ✅', 'success')
            if session['reset_email'] in otp_storage:
                del otp_storage[session['reset_email']]
            session.pop('reset_email', None)
            return redirect(url_for('login'))
        else:
            flash('Error updating password! ❌', 'error')
    
    return render_template('reset_password.html')

@app.route('/logout')
def logout():
    session.clear()
    flash('Logged out successfully! 👋', 'success')
    return redirect(url_for('index'))

@app.route('/dashboard')
def dashboard():
    if 'user_id' not in session:
        flash('Please login first! 🔒', 'error')
        return redirect(url_for('login'))
    
    user_id = session['user_id']
    user = get_user(user_id)
    
    if not user:
        session.clear()
        flash('User not found! Please login again. ❌', 'error')
        return redirect(url_for('login'))
    
    current_semester = user.get('current_semester', 1)
    show_profile_modal = user.get('profile_updated', 0) == 0
    
    subjects = get_user_subjects(user_id, current_semester)
    sessions = get_user_sessions(user_id, current_semester)
    statistics = get_study_statistics(user_id, current_semester)
    progress_data = get_progress(user_id, current_semester)
    monthly_data = get_monthly_data(user_id, current_semester)
    semester_data = get_semester_data(user_id)
    streak = get_study_streak(user_id)
    achievements = get_user_achievements(user_id)
    notes = get_study_notes(user_id)
    
    return render_template('dashboard.html', 
                         user=user,
                         subjects=subjects,
                         sessions=sessions[:10],
                         statistics=statistics,
                         progress_data=progress_data,
                         monthly_data=monthly_data,
                         semester_data=semester_data,
                         current_semester=current_semester,
                         show_profile_modal=show_profile_modal,
                         streak=streak,
                         achievements=achievements,
                         notes=notes)

@app.route('/leaderboard')
def leaderboard():
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    users = get_leaderboard()
    return render_template('leaderboard.html', users=users)

@app.route('/update_profile', methods=['POST'])
def update_profile():
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    full_name = request.form['full_name']
    university = request.form['university']
    current_semester = int(request.form['current_semester'])
    user = get_user(session['user_id'])
    old_semester = user.get('current_semester', 1) if user else 1
    
    sync_semester_records_on_profile_change(session['user_id'], old_semester, current_semester)
    update_user_profile(session['user_id'], full_name, university, current_semester)
    if current_semester > old_semester:
        flash(
            f'Profile updated! Semesters {old_semester}–{current_semester - 1} are marked complete; you are now on Semester {current_semester}. 📊',
            'success',
        )
    elif current_semester < old_semester:
        flash(
            f'Profile updated! Semesters {current_semester + 1}–{old_semester} are marked complete; you are now on Semester {current_semester}. 📊',
            'success',
        )
    else:
        flash('Profile updated successfully! ✅', 'success')
    return redirect(url_for('dashboard'))

# AJAX ROUTES
@app.route('/add_session_ajax', methods=['POST'])
def add_session_ajax():
    if 'user_id' not in session:
        return jsonify({'success': False, 'message': 'Please login first!'})
    
    data = request.json
    subject_id = data.get('subject_id')
    duration = data.get('duration')
    date = data.get('date')
    semester = get_user(session['user_id'])['current_semester']
    
    try:
        update_study_streak(session['user_id'])
        session_id = add_study_session(session['user_id'], subject_id, duration, date, semester)
        user = get_user(session['user_id'])
        new_achievements = check_achievements(session['user_id'], user['total_study_time'])
        
        message = '✨ Study session added!'
        if new_achievements:
            message = f'🎉 Congratulations! You earned: {", ".join(new_achievements)}'
        
        return jsonify({'success': True, 'message': message, 'session_id': session_id})
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)})

@app.route('/add_subject_ajax', methods=['POST'])
def add_subject_ajax():
    if 'user_id' not in session:
        return jsonify({'success': False, 'message': 'Please login first!'})
    
    data = request.json
    subject_name = data.get('subject_name')
    target_hours = data.get('target_hours', 100)
    semester = get_user(session['user_id'])['current_semester']
    
    try:
        subject_id = add_subject(session['user_id'], subject_name, target_hours, semester)
        return jsonify({'success': True, 'message': f'📚 Subject "{subject_name}" added!', 'subject_id': subject_id})
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)})

@app.route('/add_note_ajax', methods=['POST'])
def add_note_ajax():
    if 'user_id' not in session:
        return jsonify({'success': False, 'message': 'Please login first!'})
    
    data = request.json
    subject_id = data.get('subject_id')
    title = data.get('title')
    content = data.get('content')
    
    try:
        with get_db() as conn:
            c = conn.cursor()
            c.execute("SELECT subject_name FROM subjects WHERE id = ? AND user_id = ?", (subject_id, session['user_id']))
            subject = c.fetchone()
            subject_name = subject['subject_name'] if subject else 'Unknown'
        
        note_id = add_study_note(session['user_id'], subject_id, title, content)
        return jsonify({'success': True, 'message': '📝 Note added!', 'note_id': note_id, 'subject_name': subject_name})
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)})

@app.route('/get_note_ajax', methods=['POST'])
def get_note_ajax():
    if 'user_id' not in session:
        return jsonify({'success': False, 'message': 'Please login first!'})
    data = request.json or {}
    note_id = data.get('note_id')
    if note_id is None:
        return jsonify({'success': False, 'message': 'Invalid note'})
    try:
        note_id = int(note_id)
    except (TypeError, ValueError):
        return jsonify({'success': False, 'message': 'Invalid note'})
    note = get_study_note(note_id, session['user_id'])
    if not note:
        return jsonify({'success': False, 'message': 'Note not found'})
    return jsonify({
        'success': True,
        'note': {
            'id': note['id'],
            'subject_id': note['subject_id'],
            'title': note['title'],
            'content': note['content'] or '',
            'subject_name': note['subject_name'],
            'created_at': note['created_at'],
        },
    })

@app.route('/update_note_ajax', methods=['POST'])
def update_note_ajax():
    if 'user_id' not in session:
        return jsonify({'success': False, 'message': 'Please login first!'})
    data = request.json or {}
    note_id = data.get('note_id')
    subject_id = data.get('subject_id')
    title = (data.get('title') or '').strip()
    content = data.get('content') or ''
    try:
        note_id = int(note_id)
        subject_id = int(subject_id)
    except (TypeError, ValueError):
        return jsonify({'success': False, 'message': 'Invalid data'})
    if not title:
        return jsonify({'success': False, 'message': 'Title is required'})
    with get_db() as conn:
        c = conn.cursor()
        c.execute(
            "SELECT id FROM subjects WHERE id = ? AND user_id = ?",
            (subject_id, session['user_id']),
        )
        if not c.fetchone():
            return jsonify({'success': False, 'message': 'Invalid subject'})
    if update_study_note(note_id, session['user_id'], subject_id, title, content):
        return jsonify({'success': True, 'message': 'Note updated!'})
    return jsonify({'success': False, 'message': 'Could not update note'})

@app.route('/delete_session_ajax', methods=['POST'])
def delete_session_ajax():
    if 'user_id' not in session:
        return jsonify({'success': False, 'message': 'Please login first!'})
    
    data = request.json
    session_id = data.get('session_id')
    
    if delete_session(session_id, session['user_id']):
        return jsonify({'success': True, 'message': 'Session deleted!'})
    return jsonify({'success': False, 'message': 'Error deleting session!'})

@app.route('/delete_subject_ajax', methods=['POST'])
def delete_subject_ajax():
    if 'user_id' not in session:
        return jsonify({'success': False, 'message': 'Please login first!'})
    
    data = request.json
    subject_id = data.get('subject_id')
    
    if delete_subject(subject_id, session['user_id']):
        return jsonify({'success': True, 'message': 'Subject deleted!'})
    return jsonify({'success': False, 'message': 'Error deleting subject!'})

@app.route('/delete_note_ajax', methods=['POST'])
def delete_note_ajax():
    if 'user_id' not in session:
        return jsonify({'success': False, 'message': 'Please login first!'})
    
    data = request.json
    note_id = data.get('note_id')
    
    delete_study_note(note_id, session['user_id'])
    return jsonify({'success': True, 'message': 'Note deleted!'})

@app.route('/end_semester', methods=['POST'])
def end_semester_route():
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    user = get_user(session['user_id'])
    new_semester = end_semester(session['user_id'], user['current_semester'])
    
    if new_semester:
        flash(f'🎓 Semester {user["current_semester"]} completed! Now on Semester {new_semester}', 'success')
    else:
        flash('Error ending semester! ❌', 'error')
    
    return redirect(url_for('dashboard'))

@app.route('/report')
def report():
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    weekly_report = get_weekly_report(session['user_id'])
    monthly_report = get_monthly_report(session['user_id'])
    
    return render_template('report.html', 
                         weekly_report=weekly_report,
                         monthly_report=monthly_report,
                         user=get_user(session['user_id']))

@app.route('/api/monthly_stats')
def monthly_stats():
    if 'user_id' not in session:
        return jsonify({'error': 'Unauthorized'}), 401
    
    semester = request.args.get('semester', type=int)
    data = get_monthly_data(session['user_id'], semester)
    return jsonify(data)

@app.route('/api/semester_stats')
def semester_stats():
    if 'user_id' not in session:
        return jsonify({'error': 'Unauthorized'}), 401
    
    data = get_semester_data(session['user_id'])
    return jsonify(data)

if __name__ == '__main__':
    app.run(debug=True, threaded=True)