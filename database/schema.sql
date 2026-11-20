-- HRD Omborxona Face ID Davomat va Ish Vaqti Hisobi Tizimi
-- Ma'lumotlar bazasi sxemasi (MySQL 8.0+ / MariaDB / SQLite mos keluvchi)

CREATE TABLE IF NOT EXISTS departments (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS schedules (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    grace_period_minutes INT DEFAULT 15,
    break_minutes INT DEFAULT 60,
    work_days VARCHAR(50) DEFAULT '1,2,3,4,5,6',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS employees (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    employee_code VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(60) NOT NULL,
    last_name VARCHAR(60) NOT NULL,
    phone VARCHAR(25) NOT NULL,
    pin_code VARCHAR(10) NOT NULL,
    department_id INT,
    schedule_id INT,
    position VARCHAR(100) NOT NULL,
    hourly_rate DECIMAL(10, 2) DEFAULT 25000.00,
    avatar VARCHAR(255) DEFAULT '',
    face_photo VARCHAR(255) DEFAULT '',
    face_descriptor TEXT NULL,
    status VARCHAR(20) DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS attendances (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    check_in DATETIME NULL,
    check_out DATETIME NULL,
    check_in_photo VARCHAR(255) NULL,
    check_out_photo VARCHAR(255) NULL,
    status VARCHAR(30) DEFAULT 'absent', -- 'on_time', 'late', 'completed', 'absent'
    late_minutes INT DEFAULT 0,
    early_leave_minutes INT DEFAULT 0,
    worked_minutes INT DEFAULT 0,
    undertime_minutes INT DEFAULT 0,
    method VARCHAR(30) DEFAULT 'face_id', -- 'face_id', 'terminal', 'manual', 'pin'
    note TEXT NULL,
    telegram_sent TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE (employee_id, date)
);

CREATE TABLE IF NOT EXISTS attendance_logs (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    attendance_id INT NULL,
    employee_id INT NOT NULL,
    event_type VARCHAR(20) NOT NULL, -- 'check_in', 'check_out'
    event_time DATETIME NOT NULL,
    photo_path VARCHAR(255) NULL,
    device VARCHAR(100) DEFAULT 'Warehouse Face ID Gate #1',
    confidence_score DECIMAL(5, 2) DEFAULT 98.50,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS telegram_logs (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    event_type VARCHAR(30) NOT NULL,
    chat_id VARCHAR(50) NOT NULL,
    photo_path VARCHAR(255) NULL,
    caption TEXT NULL,
    status VARCHAR(20) NOT NULL,
    http_code INT DEFAULT 0,
    error_message TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    description VARCHAR(255),
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Indekslar
CREATE INDEX IF NOT EXISTS idx_attendances_date ON attendances(date);
CREATE INDEX IF NOT EXISTS idx_attendances_status ON attendances(status);
CREATE INDEX IF NOT EXISTS idx_employees_code ON employees(employee_code);
CREATE INDEX IF NOT EXISTS idx_employees_pin ON employees(pin_code);
CREATE INDEX IF NOT EXISTS idx_telegram_status ON telegram_logs(status, created_at);
