---
paths:
  - 'app/{Models,Services}/KnowledgeCheck*.php'
---

# Models Services

## Knowledge Checks stay formative
Knowledge Checks belong to Missions and preserve numbered attempt/response history. They never write Progress, XP, competency, course completion, or Boss Challenge state; retry creates a new attempt. A required check gates only its Mission challenge until any attempt is authoritatively submitted.
