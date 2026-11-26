-- این فایل را یک‌بار روی دیتابیس SQLite موجود اجرا کنید.
ALTER TABLE monthly_reports ADD COLUMN planned_percent REAL NOT NULL DEFAULT 0;
ALTER TABLE monthly_reports ADD COLUMN schedule_note TEXT NOT NULL DEFAULT '';
ALTER TABLE monthly_reports ADD COLUMN interviews_json TEXT NOT NULL DEFAULT '[]';
ALTER TABLE monthly_reports ADD COLUMN strengths_json TEXT NOT NULL DEFAULT '[]';
ALTER TABLE monthly_reports ADD COLUMN weaknesses_json TEXT NOT NULL DEFAULT '[]';
ALTER TABLE monthly_reports ADD COLUMN news_json TEXT NOT NULL DEFAULT '[]';
ALTER TABLE monthly_reports ADD COLUMN videos_json TEXT NOT NULL DEFAULT '[]';
