# Server-authoritative validation

All mission validation, XP changes, progress recording, and course unlocking happen server-side. The client never decides whether a student passed, how much XP they earn, or whether a course unlocks.

This was a deliberate trade-off against client-side validation, which would be faster for the student but would create a surface for cheating. The 10ms server round-trip on submit is worth the security guarantee.
