var classes = [
    {
        "name": "Snicco\\Middleware\\OpenRedirectProtection\\OpenRedirectProtection",
        "interface": false,
        "abstract": false,
        "final": true,
        "methods": [
            {
                "name": "__construct",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "handle",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "formatWhiteList",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "allSubdomains",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "allSubdomainsOfApplication",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "isSameSiteRedirect",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "isWhitelisted",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "forbiddenRedirect",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 8,
        "nbMethods": 8,
        "nbMethodsPrivate": 7,
        "nbMethodsPublic": 1,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 19,
        "ccn": 12,
        "ccnMethodMax": 6,
        "externals": [
            "Snicco\\Component\\HttpRouting\\Middleware\\Middleware",
            "InvalidArgumentException",
            "Psr\\Http\\Message\\ResponseInterface",
            "Snicco\\Component\\HttpRouting\\Http\\Psr7\\Request",
            "Snicco\\Component\\HttpRouting\\Middleware\\NextMiddleware",
            "Snicco\\Component\\StrArr\\Str",
            "Snicco\\Component\\StrArr\\Str",
            "Psr\\Http\\Message\\UriInterface",
            "Snicco\\Component\\HttpRouting\\Http\\Response\\RedirectResponse"
        ],
        "parents": [
            "Snicco\\Component\\HttpRouting\\Middleware\\Middleware"
        ],
        "implements": [],
        "lcom": 1,
        "length": 126,
        "vocabulary": 31,
        "volume": 624.23,
        "difficulty": 14.78,
        "effort": 9227.73,
        "level": 0.07,
        "bugs": 0.21,
        "time": 513,
        "intelligentContent": 42.23,
        "number_operators": 41,
        "number_operands": 85,
        "number_operators_unique": 8,
        "number_operands_unique": 23,
        "cloc": 10,
        "loc": 85,
        "lloc": 75,
        "mi": 63.25,
        "mIwoC": 37.91,
        "commentWeight": 25.34,
        "kanDefect": 0.94,
        "relativeStructuralComplexity": 196,
        "relativeDataComplexity": 1.09,
        "relativeSystemComplexity": 197.09,
        "totalStructuralComplexity": 1568,
        "totalDataComplexity": 8.73,
        "totalSystemComplexity": 1576.73,
        "package": "Snicco\\Middleware\\OpenRedirectProtection\\",
        "pageRank": 0,
        "afferentCoupling": 0,
        "efferentCoupling": 8,
        "instability": 1,
        "violations": {}
    }
]