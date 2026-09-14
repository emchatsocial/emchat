-- 006: Instagram-style message requests.
-- Each participant row carries a state: 'active' (in the normal inbox) or
-- 'request' (waiting in "Message requests" until this person accepts).
ALTER TABLE conversation_participants
  ADD COLUMN state VARCHAR(12) NOT NULL DEFAULT 'active' AFTER role;

CREATE INDEX idx_cp_user_state ON conversation_participants (user_id, state);
