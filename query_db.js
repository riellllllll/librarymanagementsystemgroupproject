const { DatabaseSync } = require('node:sqlite');
const db = new DatabaseSync('C:\\Users\\gabriel\\.local\\share\\mimocode\\mimocode.db', { open: true, readOnly: true });

// Look at the personal finance sessions that were the most productive
const pfSessions = db.prepare(`
  SELECT id, title, time_created
  FROM session
  WHERE directory LIKE '%personal finance%'
    AND title NOT LIKE '%checkpoint%'
  ORDER BY time_created DESC
`).all();

console.log(`=== PERSONAL FINANCE SESSIONS: ${pfSessions.length} ===`);
for (const s of pfSessions) {
  const date = new Date(Number(s.time_created)).toISOString().slice(0,16);
  console.log(`  [${s.id}] "${(s.title||'').slice(0,80)}" @ ${date}`);
}

// Look at what was written in those personal finance sessions 
for (const s of pfSessions.slice(0, 5)) {
  console.log(`\n--- PF Session: "${s.title}" (${s.id}) ---`);
  
  const writes = db.prepare(`
    SELECT json_extract(json_extract(p.data, '$.state.input'), '$.file_path') as fpath
    FROM message m
    JOIN part p ON p.message_id = m.id
    WHERE m.session_id = ?
      AND json_extract(m.data, '$.role') = 'assistant'
      AND json_extract(p.data, '$.type') = 'tool'
      AND json_extract(p.data, '$.tool') IN ('write', 'edit')
  `).all(s.id);
  
  for (const w of writes) {
    console.log(`  WRITE/EDIT: ${w.fpath}`);
  }
  
  // Also get user messages
  const userMsgs = db.prepare(`
    SELECT json_extract(p.data, '$.text') as text
    FROM message m
    JOIN part p ON p.message_id = m.id
    WHERE m.session_id = ?
      AND json_extract(m.data, '$.role') = 'user'
      AND json_extract(p.data, '$.type') = 'text'
  `).all(s.id);
  
  for (const msg of userMsgs) {
    const text = (msg.text || '').replace(/\n/g, ' ').replace(/\r/g, '').slice(0, 150);
    console.log(`  USER: ${text}`);
  }
}

// Check ALL sessions for repeated tool patterns
console.log("\n=== TOOL USAGE SUMMARY (all sessions, all projects) ===");
const toolSummary = db.prepare(`
  SELECT json_extract(p.data, '$.tool') as tool_name,
         count(*) as cnt
  FROM message m
  JOIN part p ON p.message_id = m.id
  WHERE json_extract(m.data, '$.role') = 'assistant'
    AND json_extract(p.data, '$.type') = 'tool'
  GROUP BY tool_name
  ORDER BY cnt DESC
`).all();

for (const t of toolSummary) {
  console.log(`  ${t.tool_name}: ${t.cnt}`);
}

// Check what the "Cannot publish branch" session did (git troubleshooting)
console.log("\n=== GIT TROUBLESHOOTING SESSION ===");
const gitSession = db.prepare(`
  SELECT id, title FROM session WHERE title LIKE '%Cannot publish%'
`).get();

if (gitSession) {
  const gitMessages = db.prepare(`
    SELECT json_extract(m.data, '$.role') as role,
           json_extract(p.data, '$.type') as part_type,
           json_extract(p.data, '$.text') as text,
           json_extract(p.data, '$.tool') as tool_name,
           json_extract(p.data, '$.state.input') as tool_input,
           json_extract(p.data, '$.state.output') as tool_output
    FROM message m
    JOIN part p ON p.message_id = m.id
    WHERE m.session_id = ?
    ORDER BY m.time_created ASC
  `).all(gitSession.id);
  
  for (const msg of gitMessages) {
    if (msg.role === 'user' && msg.part_type === 'text') {
      console.log(`  USER: ${(msg.text||'').slice(0,150)}`);
    } else if (msg.role === 'assistant' && msg.part_type === 'text') {
      console.log(`  ASSISTANT: ${(msg.text||'').replace(/\n/g,' ').slice(0,200)}`);
    } else if (msg.role === 'assistant' && msg.part_type === 'tool') {
      const inp = msg.tool_input || '';
      let cmd = '';
      try { cmd = JSON.parse(inp).command || ''; } catch(e) { cmd = inp.slice(0,80); }
      console.log(`  [${msg.tool_name}] ${cmd.slice(0,120)}`);
      const out = (msg.tool_output || '').toString().slice(0,200);
      if (out) console.log(`    OUTPUT: ${out.replace(/\n/g, ' ')}`);
    }
  }
}

db.close();
