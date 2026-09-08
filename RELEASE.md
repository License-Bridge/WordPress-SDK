# Releasing the SDK

Internal notes for License Bridge maintainers.

## Checklist

1. Update `composer.json` `version` to match the release.
2. Add an entry to `CHANGELOG.md`.
3. Commit and tag:

```bash
git add CHANGELOG.md README.md composer.json
git commit -m "Release X.Y.Z"
git tag X.Y.Z
git push origin master
git push origin X.Y.Z
```

4. Confirm the new tag appears on [Packagist](https://packagist.org/packages/license-bridge/wordpress-sdk) (auto-sync if configured).

## Tag naming

Use semver without a `v` prefix, e.g. `1.0.30` (consistent with existing tags).
