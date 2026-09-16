# Sandboxed iframe for live preview

Student code renders in a sandboxed iframe using srcdoc. The "Run" button produces an instant preview with no server call. The "Submit" button sends code to Laravel for authoritative validation.

We considered server-side rendering for preview but rejected it: a server round-trip on every "Run" click would slow down the trial-and-error loop that learning depends on. The iframe gives instant feedback at zero server cost.

The sandbox attribute starts with `allow-scripts` only. If a future mission genuinely needs additional permissions (e.g. `allow-same-origin` for a CSS challenge that reads computed styles), we add the minimum required permission and note it here. The bar for expanding sandbox permissions is high: the mission must not work without it, and the security implications must be documented.

The trade-off is that client-side preview can diverge from server-side rendering. We accept this. Preview is feedback, not proof. The server decides pass or fail at submit time.
