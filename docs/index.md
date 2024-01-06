# Introduction

[Laravel Wallet](https://github.com/021-projects/laravel-wallet) was inspired by the idea of implementing a transaction engine in PHP that was as reliable and flexible as banking systems.

## Advantages

### Reliability
The main task of the Laravel Wallet is to implement a reliable transaction mechanism. During development, all attention was focused on the security of this mechanism in order to prevent leakage of funds under the most unforeseen circumstances.
Before the package publication, it was in private access for several years, supporting the work of several financial projects. During this period, we encountered leaks for various reasons and closed these vulnerabilities. This means that the reliability of transactions in this package has been tested not only by a tests, but also by time.

### Syntax Sugar
The package provides several intuitive [helpers](helpers.md) and [interfaces](interfaces.md) that make interacting with transactions intuitive, simple and concise.

### Reliable work with numbers
In PHP, the `int` and `float` number types have limitations that prevent them from working accurately with very large numbers or very small numbers.
Therefore, to more accurately work with numbers, the library uses the [Numeric](interfaces.md#numeric) class, which, in turn, uses the [bcmath](https://www.php.net/manual/en/book.bc.php) module for calculations.
This ensures reliable operation with numbers that typically appear when working with cryptocurrencies.
