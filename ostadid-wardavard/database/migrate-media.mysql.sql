-- این فایل را یک‌بار روی دیتابیس موجود اجرا کنید.
ALTER TABLE monthly_reports
 ADD COLUMN planned_percent DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER cumulative_percent,
 ADD COLUMN schedule_note TEXT NOT NULL AFTER planned_percent,
 ADD COLUMN interviews_json JSON NOT NULL AFTER interview_text,
 ADD COLUMN strengths_json JSON NOT NULL AFTER interviews_json,
 ADD COLUMN weaknesses_json JSON NOT NULL AFTER strengths_json,
 ADD COLUMN news_json JSON NOT NULL AFTER weaknesses_json,
 ADD COLUMN videos_json JSON NOT NULL AFTER news_json;
