var classes = [
    {
        "name": "Snicco\\Middleware\\ShareCookies\\ShareCookies",
        "interface": false,
        "abstract": false,
        "final": true,
        "methods": [
            {
                "name": "addCookiesToResponse",
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
            }
        ],
        "nbMethodsIncludingGettersSetters": 2,
        "nbMethods": 2,
        "nbMethodsPrivate": 1,
        "nbMethodsPublic": 1,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 4,
        "ccn": 3,
        "ccnMethodMax": 3,
        "externals": [
            "Snicco\\Component\\HttpRouting\\Middleware\\Middleware",
            "Snicco\\Component\\HttpRouting\\Http\\Psr7\\Response",
            "Snicco\\Component\\HttpRouting\\Http\\Psr7\\Response",
            "Psr\\Http\\Message\\ResponseInterface",
            "Snicco\\Component\\HttpRouting\\Http\\Psr7\\Request",
            "Snicco\\Component\\HttpRouting\\Middleware\\NextMiddleware"
        ],
        "parents": [
            "Snicco\\Component\\HttpRouting\\Middleware\\Middleware"
        ],
        "lcom": 1,
        "length": 27,
        "vocabulary": 11,
        "volume": 93.4,
        "difficulty": 5.43,
        "effort": 507.05,
        "level": 0.18,
        "bugs": 0.03,
        "time": 28,
        "intelligentContent": 17.21,
        "number_operators": 8,
        "number_operands": 19,
        "number_operators_unique": 4,
        "number_operands_unique": 7,
        "cloc": 0,
        "loc": 20,
        "lloc": 20,
        "mi": 57.42,
        "mIwoC": 57.42,
        "commentWeight": 0,
        "kanDefect": 0.45,
        "relativeStructuralComplexity": 16,
        "relativeDataComplexity": 0.9,
        "relativeSystemComplexity": 16.9,
        "totalStructuralComplexity": 32,
        "totalDataComplexity": 1.8,
        "totalSystemComplexity": 33.8,
        "package": "Snicco\\Middleware\\ShareCookies\\",
        "pageRank": 0,
        "afferentCoupling": 0,
        "efferentCoupling": 5,
        "instability": 1,
        "violations": {}
    }
]