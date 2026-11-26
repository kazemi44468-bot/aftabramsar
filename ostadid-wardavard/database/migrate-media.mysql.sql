-- این فایل را یک‌بار روی دیتابیس موجود اجرا کنید.
ALTER TABLE monthly_reports
 ADD COLUMN planned_percent DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER cumulative_percent,
 ADD COLUMN schedule_note TEXT NULL AFTER planned_percent,
 ADD COLUMN interviews_json JSON NULL AFTER interview_text,
 ADD COLUMN strengths_json JSON NULL AFTER interviews_json,
 ADD COLUMN weaknesses_json JSON NULL AFTER strengths_json,
 ADD COLUMN news_json JSON NULL AFTER weaknesses_json,
 ADD COLUMN videos_json JSON NULL AFTER news_json;

UPDATE monthly_reports SET
 schedule_note = COALESCE(schedule_note, ''),
 interviews_json = COALESCE(interviews_json, JSON_ARRAY()),
 strengths_json = COALESCE(strengths_json, JSON_ARRAY()),
 weaknesses_json = COALESCE(weaknesses_json, JSON_ARRAY()),
 news_json = COALESCE(news_json, JSON_ARRAY()),
 videos_json = COALESCE(videos_json, JSON_ARRAY());

ALTER TABLE monthly_reports
 MODIFY interviews_json JSON NOT NULL,
 MODIFY strengths_json JSON NOT NULL,
 MODIFY weaknesses_json JSON NOT NULL,
 MODIFY news_json JSON NOT NULL,
 MODIFY videos_json JSON NOT NULL;
