-- 007: "Delete chat" — a per-person clear point. Messages before this time are
-- hidden from that person and the thread drops out of their inbox until a new
-- message arrives.
ALTER TABLE conversation_participants
  ADD COLUMN cleared_at DATETIME NULL AFTER last_read_at;
