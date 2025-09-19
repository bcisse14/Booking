-- init_schema.sql : DDL for Postgres (Supabase)

-- Création de la table slot
CREATE TABLE IF NOT EXISTS slot (
  id SERIAL PRIMARY KEY,
  datetime TIMESTAMP WITH TIME ZONE NOT NULL,
  reserved BOOLEAN NOT NULL DEFAULT FALSE
);

-- Création de la table "user"
CREATE TABLE IF NOT EXISTS "user" (
  id SERIAL PRIMARY KEY,
  email VARCHAR(180) NOT NULL,
  password VARCHAR(255) NOT NULL,
  roles JSONB NOT NULL DEFAULT '[]'::jsonb
);
CREATE UNIQUE INDEX IF NOT EXISTS ux_user_email ON "user"(email);

-- Création de la table appointment
CREATE TABLE IF NOT EXISTS appointment (
  id SERIAL PRIMARY KEY,
  slot_id INTEGER,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  confirmed BOOLEAN NOT NULL DEFAULT FALSE,
  cancelled BOOLEAN NOT NULL DEFAULT FALSE,
  cancel_token VARCHAR(64),
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now()
);

CREATE UNIQUE INDEX IF NOT EXISTS ux_appointment_cancel_token ON appointment(cancel_token);
CREATE INDEX IF NOT EXISTS idx_appointment_slot_id ON appointment(slot_id);

-- Add FK constraint only if it does not already exist
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM pg_constraint WHERE conname = 'fk_appointment_slot'
  ) THEN
    ALTER TABLE appointment
      ADD CONSTRAINT fk_appointment_slot
      FOREIGN KEY (slot_id) REFERENCES slot (id) ON DELETE SET NULL;
  END IF;
END
$$;
