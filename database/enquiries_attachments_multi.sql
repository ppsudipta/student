-- Allow multiple enquiry attachment filenames (comma-separated, up to 5).
ALTER TABLE enquiries MODIFY attachment TEXT NULL;
ALTER TABLE enquiry_messages MODIFY attachment TEXT NULL;
