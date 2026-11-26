PRAGMA foreign_keys = ON;
CREATE TABLE IF NOT EXISTS requests (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 tracking_code TEXT NOT NULL UNIQUE,
 request_type TEXT NOT NULL,
 full_name TEXT NOT NULL,
 mobile TEXT NOT NULL,
 subject TEXT NOT NULL,
 description TEXT NOT NULL,
 status TEXT NOT NULL DEFAULT 'ثبت اولیه',
 admin_note TEXT NOT NULL DEFAULT '',
 created_at TEXT NOT NULL,
 updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS monthly_reports (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 year INTEGER NOT NULL,
 month INTEGER NOT NULL,
 title TEXT NOT NULL,
 cumulative_percent REAL NOT NULL,
 summary TEXT NOT NULL,
 event_text TEXT NOT NULL DEFAULT '',
 interview_text TEXT NOT NULL DEFAULT '',
 actions_json TEXT NOT NULL DEFAULT '[]',
 gallery_json TEXT NOT NULL DEFAULT '[]',
 next_steps TEXT NOT NULL DEFAULT '',
 published INTEGER NOT NULL DEFAULT 0,
 created_at TEXT NOT NULL,
 UNIQUE(year, month)
);
CREATE TABLE IF NOT EXISTS announcements (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 title TEXT NOT NULL,
 category TEXT NOT NULL,
 body TEXT NOT NULL,
 published_at TEXT NOT NULL,
 published INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS idx_requests_tracking ON requests(tracking_code);
CREATE INDEX IF NOT EXISTS idx_reports_period ON monthly_reports(year, month);
