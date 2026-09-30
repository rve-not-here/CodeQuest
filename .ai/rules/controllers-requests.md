---
paths:
  - 'app/Http/{Controllers,Requests}/KnowledgeCheck*.php'
---

# Controllers Requests

## Knowledge Check answers are server-authoritative
Before submission, student presentation may expose prompts, option ids/text, and display metadata only. Correct flags and explanations remain server-side. Submission validates the complete question set and option ownership, scores atomically, and cross-user or cross-Mission attempts return 404.
