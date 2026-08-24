import sqlite3

# Connect to the database file (creates it if it doesn't exist)
conn = sqlite3.connect("../hsts_history.db")

# Create a cursor object (this executes your SQL statements)
cursor = conn.cursor()
cursor.execute(
    '''
    CREATE TABLE commits
    (
        sha       TEXT PRIMARY KEY,
        timestamp TEXT NOT NULL,
        message   TEXT NOT NULL
    );
    ''')
cursor.execute(
    '''
    CREATE TABLE domain_events
    (
        domain     TEXT NOT NULL,
        commit_sha TEXT NOT NULL,
        action     TEXT NOT NULL CHECK (action IN ('a', 'r', 'm')),
        policy     TEXT,
        subdomains BOOL,

        FOREIGN KEY (commit_sha) REFERENCES commits (sha)
    );
    ''')
cursor.execute(
    '''
    CREATE INDEX idx_domain_events_domain
        ON domain_events (domain);
    ''')
cursor.execute(
    '''
    CREATE INDEX idx_domain_events_commit
        ON domain_events (commit_sha);
    ''')
cursor.execute(
    '''
    CREATE INDEX idx_commits_sha
        ON commits (sha);
    ''')
cursor.execute(
    '''
    CREATE INDEX idx_commits_time
        ON commits (timestamp);
    ''')
conn.commit()
