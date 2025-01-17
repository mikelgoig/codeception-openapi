<h1>
    Codeception Module for contract testing with OpenAPI
</h1>

<p>
Created by <a href="https://mikelgoig.com">Mikel Goig</a>.
</p>

<p>
    <a href="https://github.com/mikelgoig/codeception-openapi">
        View Repository
    </a>
</p>

---

[![Packagist Version](https://img.shields.io/packagist/v/mikelgoig/codeception-openapi)](https://packagist.org/packages/mikelgoig/codeception-openapi)
[![Packagist Downloads](https://img.shields.io/packagist/dt/mikelgoig/codeception-openapi)](https://packagist.org/packages/mikelgoig/codeception-openapi/stats)
[![Packagist PHP Version](https://img.shields.io/packagist/dependency-v/mikelgoig/codeception-openapi/php)](https://thephp.foundation)

**This Codeception module provides you with actions to validate API requests and responses with
an [OpenAPI](https://openapis.org) Specification.**

It requires [REST](https://codeception.com/docs/modules/REST)
and [Symfony](https://codeception.com/docs/modules/Symfony) modules.

It supports [Gherkin format](https://codeception.com/docs/BDD).

## 😎 Installation

1. Install this package using Composer:

    ```bash
    composer require --dev mikelgoig/codeception-openapi
    ```

## 🛠️ Configuration

1. Add the Codeception module to your config file:

    ```yml
    modules:
      enabled:
        - MikelGoig\Codeception\Module\OpenApi:
            depends: [ REST, Symfony ]
            openapi: path/to/openapi.yaml
            multipart_boundary: foo
    ```

    * `openapi` - the path of the OpenAPI file
    * `multipart_boundary` *optional* - the boundary parameter for multipart requests

2. To set up Gherkin steps, enable the `gherkin` part of the module:

    ```yml
    modules:
      enabled:
        - MikelGoig\Codeception\Module\OpenApi:
            # ...
            part: gherkin
    ```
