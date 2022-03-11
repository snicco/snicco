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
                "public": true,
                "private": false,
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
        "nbMethodsPrivate": 6,
        "nbMethodsPublic": 2,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 22,
        "ccn": 15,
        "ccnMethodMax": 7,
        "externals": [
            "Snicco\\Component\\HttpRouting\\Middleware\\Middleware",
            "InvalidArgumentException",
            "Psr\\Http\\Message\\ResponseInterface",
            "Snicco\\Component\\HttpRouting\\Http\\Psr7\\Request",
            "Snicco\\Component\\HttpRouting\\Middleware\\NextMiddleware",
            "Snicco\\Component\\StrArr\\Str",
            "Snicco\\Component\\StrArr\\Str",
            "Snicco\\Component\\HttpRouting\\Http\\Psr7\\Request",
            "Snicco\\Component\\HttpRouting\\Http\\Response\\RedirectResponse"
        ],
        "parents": [
            "Snicco\\Component\\HttpRouting\\Middleware\\Middleware"
        ],
        "lcom": 1,
        "length": 138,
        "vocabulary": 33,
        "volume": 696.13,
        "difficulty": 14.72,
        "effort": 10246.98,
        "level": 0.07,
        "bugs": 0.23,
        "time": 569,
        "intelligentContent": 47.29,
        "number_operators": 46,
        "number_operands": 92,
        "number_operators_unique": 8,
        "number_operands_unique": 25,
        "cloc": 11,
        "loc": 89,
        "lloc": 78,
        "mi": 62.71,
        "mIwoC": 36.8,
        "commentWeight": 25.91,
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
        "efferentCoupling": 7,
        "instability": 1,
        "violations": {}
    }
]