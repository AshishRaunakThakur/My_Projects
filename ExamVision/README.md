# ExamVision — Secure Online Examination System

## About
ExamVision is a secure, fullscreen-locked online examination system with automatic proctoring features including tab-switch detection, copy/paste blocking, and violation logging.

## Features

### For Students
- Fullscreen Locked Exams - Cannot exit without violation
- Tab Switch Detection - Automatic violation logging
- Copy/Paste Block - Completely disabled during exam
- Keyboard Shortcuts Block - Ctrl/Cmd/Alt keys disabled
- Random Question Sets - Different sets based on enrollment number
- Result Viewing - See scores after teacher declaration

### For Teachers
- Create Exams - Normal or Random Sets exam types
- Target Audience - Select specific departments and semesters
- Exam Analysis - Score distribution, pass rates, violation logs
- View Student Answers - Detailed answer sheet review
- Re-exam Students - Allow retake for individual or all students
- Flash Results - Release results to students

### For Admin
- User Management - Enable/disable accounts, reset passwords
- System Cleanup - Delete users or exams as needed

## Installation

### Prerequisites
- Python 3.8+
- XAMPP (MySQL) or any MySQL server

### Step 1: Setup Database
1. Start MySQL from XAMPP Control Panel
2. Open phpMyAdmin or MySQL console
3. Run the `setupmysql.sql` file

### Step 2: Install Dependencies
```bash
pip install -r requirements.txt
```

### Step 3: Configure `.env` File
Create `.env` file in root directory:
```env
SECRET_KEY=exam-secret-key-2024-xampp
MYSQL_USER=root
MYSQL_PASSWORD=
MYSQL_HOST=localhost
MYSQL_PORT=3306
MYSQL_DB=online_exam_system
```

### Step 4: Run the Application
```bash
python app.py
```

Access at: **http://localhost:5000**

## Login Credentials

| Role | Username | Password | Email Domain |
|------|----------|----------|--------------|
| Admin | admin | admin123 | - |
| Teacher | teacher | teacher123 | @marwadiuniversity.edu.in |
| Student | Auto-generated | Set during registration | @marwadiuniversity.ac.in |

## Email Rules
- Students: Must use `@marwadiuniversity.ac.in`
- Teachers/Faculty: Must use `@marwadiuniversity.edu.in`
- Username is automatically extracted from email (largest number found)

## Project Structure
```
ExamVision/
├── app.py                 # Main Flask application
├── requirements.txt       # Python dependencies
├── setupmysql.sql         # Database creation script
├── .env                   # Environment variables
├── README.md              # This file
└── templates/             # All HTML templates
    ├── index.html
    ├── login.html
    ├── register.html
    ├── student_dashboard.html
    ├── teacher_dashboard.html
    ├── admin_dashboard.html
    ├── exam.html
    ├── create_exam.html
    ├── edit_exam.html
    ├── edit_exam_random.html
    ├── add_questions_normal.html
    ├── add_questions_random.html
    ├── exam_analysis.html
    ├── view_student_answers.html
    ├── student_results.html
    └── student_result_detail.html
```

## Security Features

| Feature | Implementation |
|---------|----------------|
| Fullscreen Lock | Required at exam start, exit = violation |
| Tab Switching | Detected via visibilitychange API |
| Copy/Paste | Prevented via copy and paste event listeners |
| Keyboard Shortcuts | preventDefault() on Ctrl, Alt, Meta keys |
| Right Click | Disabled via contextmenu event |
| Back Navigation | Blocked via popstate event |
| Page Leave Warning | beforeunload event with warning |
| Auto-Submit | After 3 violations or time expiration |

## Exam Types

### Normal Exam
- All students get the same set of questions
- Simple to create and manage

### Random Sets Exam
- Multiple question sets (2-6 sets)
- Students assigned set based on enrollment number (consistent)
- Different questions for different students
- Prevents answer sharing

## Teacher Features

### Exam Analysis Page Shows
- Total students attempted
- Average score and pass rate
- Score distribution chart (percentage-based)
- Violation types chart
- Student-wise results table with re-exam option
- Cheating violation logs

### Result Management
- Flash Result button - Release results to students
- Results hidden until teacher declares
- Students can view detailed answer sheets after declaration

## Troubleshooting

### Issue: Table doesn't exist
Solution: Stop the app, delete database, restart app (tables auto-create)

### Issue: Cannot register (invalid email)
Solution: Use correct domain - @marwadiuniversity.ac.in (students) or @marwadiuniversity.edu.in (teachers)

### Issue: Exam not showing on dashboard
Solution: Check:
- Exam is marked Active
- Current date matches scheduled_date
- Current time is between start_time and end_time
- Student's department/semester matches target (if set)

### Issue: Random exam student gets wrong set
Solution: Student must set enrollment number in profile before starting exam

## Developers

Developed by:
- Meenal Patro
- Ashish Raunak
- Nitin Kumar

## License

Educational Project - Marwadi University