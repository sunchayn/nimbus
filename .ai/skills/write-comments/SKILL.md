---
name: write-comments
description: "Comment rules for PHP, TypeScript, and Vue in Nimbus. Apply when writing or reviewing any comment."
license: MIT
metadata:
  author: sunchayn
---

# Write Comments

Comments are rare. Each states one technical fact plainly in three lines or fewer.

## Rules

1. Comment why, never what. Skip a comment that restates the next line.
2. Comment non-obvious decisions, ordering constraints, workarounds, and dense expressions like regexes.
3. In AST code, say which PHP syntax a PhpParser node class represents, and why a fast path (reflection) runs before a slow one (AST).
4. Never reference tickets, issue links, or the originating request.
5. Plain language. No metaphors or idioms.
6. No semicolon or colon, except the colon in a URL. Split into two sentences and drop the weaker one.
7. Inline comments are 3 lines at most. Longer goes in a PHPDoc block or gets trimmed.
8. Wrap only after a clause or comma. The last line has at least 4 words.
9. PHPDoc states the contract: parameters, return, exceptions. It never names internal variables or describes steps. Put that inline at the line it explains.
10. In TypeScript and Vue, use `//` inline and `/** */` for exported functions and types.

## Examples

- Bad: `// Magic happens here to handle cookie encryption.`
- Good: `// Prefix the cookie value with the app key so Laravel can decrypt it.`

## Related

- [write-php-code](../write-php-code/SKILL.md)
- [write-frontend-code](../write-frontend-code/SKILL.md)
