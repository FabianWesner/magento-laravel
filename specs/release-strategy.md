# Modernization Release Strategy Spec

Canonical release instructions live in `specs/modernization/release-strategy.md`.

The default release model is a route-by-route strangler migration with feature flags and a legacy fallback until the target route or workflow passes staging and production observation.

