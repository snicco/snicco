var classes = [
    {
        "name": "Snicco\\Middleware\\Negotiation\\NegotiateContent",
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
                "name": "defaultConfiguration",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "next",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 4,
        "nbMethods": 4,
        "nbMethodsPrivate": 2,
        "nbMethodsPublic": 2,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 7,
        "ccn": 4,
        "ccnMethodMax": 3,
        "externals": [
            "Snicco\\Component\\HttpRouting\\Middleware\\Middleware",
            "Psr\\Http\\Message\\ResponseInterface",
            "Snicco\\Component\\HttpRouting\\Http\\Psr7\\Request",
            "Snicco\\Component\\HttpRouting\\Middleware\\NextMiddleware",
            "Middlewares\\ContentType",
            "Middlewares\\ContentLanguage",
            "Snicco\\Component\\Psr7ErrorHandler\\HttpException",
            "Snicco\\Component\\HttpRouting\\Middleware\\NextMiddleware",
            "Middlewares\\ContentLanguage",
            "Snicco\\Component\\HttpRouting\\Middleware\\NextMiddleware",
            "Snicco\\Component\\HttpRouting\\Middleware\\NextMiddleware"
        ],
        "parents": [
            "Snicco\\Component\\HttpRouting\\Middleware\\Middleware"
        ],
        "lcom": 1,
        "length": 75,
        "vocabulary": 28,
        "volume": 360.55,
        "difficulty": 5.25,
        "effort": 1892.9,
        "level": 0.19,
        "bugs": 0.12,
        "time": 105,
        "intelligentContent": 68.68,
        "number_operators": 12,
        "number_operands": 63,
        "number_operators_unique": 4,
        "number_operands_unique": 24,
        "cloc": 15,
        "loc": 51,
        "lloc": 36,
        "mi": 84.85,
        "mIwoC": 47.61,
        "commentWeight": 37.24,
        "kanDefect": 0.22,
        "relativeStructuralComplexity": 81,
        "relativeDataComplexity": 0.58,
        "relativeSystemComplexity": 81.58,
        "totalStructuralComplexity": 324,
        "totalDataComplexity": 2.3,
        "totalSystemComplexity": 326.3,
        "package": "Snicco\\Middleware\\Negotiation\\",
        "pageRank": 0,
        "afferentCoupling": 0,
        "efferentCoupling": 7,
        "instability": 1,
        "violations": {}
    }
]