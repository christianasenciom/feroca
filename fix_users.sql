DO $$
DECLARE
    rec RECORD;
    nextid integer;
BEGIN
    SELECT COALESCE(MAX(id),0) INTO nextid FROM auth.users;

    FOR rec IN
        SELECT a.ctid
        FROM auth.users a
        WHERE EXISTS (
            SELECT 1 FROM auth.users b WHERE b.id = a.id AND b.ctid < a.ctid
        )
        ORDER BY a.ctid
    LOOP
        nextid := nextid + 1;
        EXECUTE format('UPDATE auth.users SET id = %s WHERE ctid = %L', nextid, rec.ctid);
    END LOOP;

    PERFORM setval('auth.users_id_seq', (SELECT MAX(id) FROM auth.users));

    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conrelid = 'auth.users'::regclass AND contype = 'p'
    ) THEN
        EXECUTE 'ALTER TABLE auth.users ADD PRIMARY KEY (id)';
    END IF;
END$$;

SELECT 'done' as status;
SELECT id, COUNT(*) as cnt FROM auth.users GROUP BY id HAVING COUNT(*)>1;
SELECT MAX(id) as max_id FROM auth.users;
SELECT id, ctid, email, persona_id FROM auth.users WHERE id = 1 ORDER BY ctid;
