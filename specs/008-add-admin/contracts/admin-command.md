# Contract: `admin:create` command

```text
php artisan admin:create {email}
```

| Argument | Required | Notes |
|----------|----------|-------|
| `email` | Yes | Trimmed and lowercased before use |

There is no password argument or option (FR-002).

## Prompts

| Label | Input |
|-------|-------|
| `Password` | Hidden |
| `Confirm password` | Hidden |

If validation fails, the reasons are shown and both prompts repeat.

## Outcomes

| Situation | Output | Exit code | Data |
|-----------|--------|-----------|------|
| Valid email and password | Success message naming the email | 0 | Admin created |
| Malformed or over-long email | Validation message, before any prompt | 1 | None |
| Email already belongs to an admin, in any letter case | Validation message, before any prompt | 1 | Existing admin unchanged |
| Input isn't interactive (`--no-interaction`, or no TTY) | Message that the command must be run interactively | 1 | None |
| Password invalid or confirmation doesn't match | Reasons, then prompts repeat | Continues | None yet |
| Operator cancels at a prompt | None required | Non-zero | None |
| Duplicate from a concurrent run | Same message as an existing admin | 1 | Existing admin unchanged |

The password is never written to output or logs. An email that belongs to a regular user is
accepted, and that user is unaffected.
