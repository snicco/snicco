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
                "public": false,
                "private": true,
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
        "nbMethodsPrivate": 3,
        "nbMethodsPublic": 1,
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
        "implements": [],
        "lcom": 1,
        "length": 70,
        "vocabulary": 26,
        "volume": 329.03,
        "difficulty": 5.36,
        "effort": 1764.8,
        "level": 0.19,
        "bugs": 0.11,
        "time": 98,
        "intelligentContent": 61.34,
        "number_operators": 11,
        "number_operands": 59,
        "number_operators_unique": 4,
        "number_operands_unique": 22,
        "cloc": 18,
        "loc": 52,
        "lloc": 34,
        "mi": 87.95,
        "mIwoC": 48.43,
        "commentWeight": 39.52,
        "kanDefect": 0.22,
        "relativeStructuralComplexity": 81,
        "relativeDataComplexity": 0.48,
        "relativeSystemComplexity": 81.48,
        "totalStructuralComplexity": 324,
        "totalDataComplexity": 1.9,
        "totalSystemComplexity": 325.9,
        "package": "Snicco\\Middleware\\Negotiation\\",
        "pageRank": 0,
        "afferentCoupling": 0,
        "efferentCoupling": 7,
        "instability": 1,
        "violations": {}
    }
]