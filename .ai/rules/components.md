---
paths:
  - resources/views/components/theme-toggle.blade.php
---

# Components

## The theme control sets a mode, it never flips one
The control is two options, Light and Dark, each naming the mode it applies; the option in force is the pressed one (aria-pressed). It replaced a single icon toggle: an icon standing for 'the other mode' is ambiguous, and a student who wanted light clicked the sun and got dark, so the account stored the wrong choice. There is no third 'System' option - the choice stays in force until the student changes it. Pressing the option already in force changes nothing on the page but still carries the choice to the account.
