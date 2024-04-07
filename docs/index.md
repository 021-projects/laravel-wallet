---
# https://vitepress.dev/reference/default-theme-home-page
layout: home
titleTemplate: false

hero:
  name: "Laravel Wallet"
  text: "Designed for reliable and flexible transactions"
  image: /code-hero.png
  actions:
    - theme: brand
      text: Get Started
      link: /getting-started
    - theme: alt
      text: Star on GitHub ⭐
      link: https://github.com/021-projects/laravel-wallet
features:
- icon: 🔒
  title: Reliability
  details: The package underwent private development for years, supporting multiple financial projects before its release. Despite experiencing leaks, these incidents helped us identify and fix vulnerabilities not caught by tests. Consequently, the package now includes enhanced safeguards against emergency fund leakages.
  link: /reliability
- icon: 🔢
  title: Unlimited Numbers
  details: In PHP, the int and float types struggle with very large or small numbers. The library adopts the Numeric class, leveraging the bcmath module, for precise calculations. This approach guarantees accurate handling of the extensive numerical ranges common in cryptocurrency operations.
  link: https://github.com/021-projects/numeric
- icon: 🍬
  title: Syntax Sugar
  details: Intuitive helpers and interfaces for easy, straightforward, and concise transaction management. The package syntax is inspired by the simplicity and convenience of Laravel and the Ethereum blockchain implementation.
  link: https://github.com/021-projects/laravel-wallet/blob/v9.x-dev/src/helpers.php
---

