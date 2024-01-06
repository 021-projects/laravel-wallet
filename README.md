<a href="https://packagist.org/packages/021/laravel-wallet"><img src="https://img.shields.io/packagist/dt/021/laravel-wallet" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/021/laravel-wallet"><img src="https://img.shields.io/packagist/v/021/laravel-wallet" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/021/laravel-wallet"><img src="https://img.shields.io/packagist/l/021/laravel-wallet" alt="License"></a>

# Introduction

[Laravel Wallet](https://github.com/021-projects/laravel-wallet) was inspired by the idea of implementing a transaction engine in PHP that was as reliable and flexible as banking systems.

## Advantages

### Reliability
The main task of the Laravel Wallet is to implement a reliable transaction mechanism. During development, all attention was focused on the security of this mechanism in order to prevent leakage of funds under the most unforeseen circumstances.
Before the package publication, it was in private access for several years, supporting the work of several financial projects. During this period, we encountered leaks for various reasons and closed these vulnerabilities. This means that the reliability of transactions in this package has been tested not only by a tests, but also by time.

### Syntax Sugar
The package provides several intuitive [helpers](https://021-projects.github.io/laravel-wallet/helpers.html) and [interfaces](https://021-projects.github.io/laravel-wallet/interfaces.html) that make interacting with transactions intuitive, simple and concise.

### Reliable work with numbers
In PHP, the `int` and `float` number types have limitations that prevent them from working accurately with very large numbers or very small numbers.
Therefore, to more accurately work with numbers, the library uses the [Numeric](https://021-projects.github.io/laravel-wallet/interfaces.html#numeric) class, which, in turn, uses the [bcmath](https://www.php.net/manual/en/book.bc.php) module for calculations.
This ensures reliable operation with numbers that typically appear when working with cryptocurrencies.

# Documentation
- [Getting Started](https://021-projects.github.io/laravel-wallet/getting-started.html)
- [Configuration](https://021-projects.github.io/laravel-wallet/configuration.html)
- [Basic Usage](https://021-projects.github.io/laravel-wallet/basic-usage.html)

### Donate
#### Bitcoin
1G4U12A7VVVaUrmj4KmNt4C5SaDmCXuW49
#### Litecoin
LXjysogo9AHiNE7AnUm4zjprDzCCWVESai
#### Ethereum
0xd23B42D0A84aB51a264953f1a9c9A393c5Ffe4A1
#### Tron
TWEcfzu2UAPsbotZJh8DrEpvdZGho79jTg
