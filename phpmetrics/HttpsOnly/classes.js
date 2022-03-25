var classes = [
    {
        "name": "Snicco\\Middleware\\HttpsOnly\\HttpsOnly",
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
            "Psr\\Http\\Message\\ResponseInterface",
            "Snicco\\Component\\HttpRouting\\Http\\Psr7\\Request",
            "Snicco\\Component\\HttpRouting\\Middleware\\NextMiddleware"
        ],
        "parents": [
            "Snicco\\Component\\HttpRouting\\Middleware\\Middleware"
        ],
        "implements": [],
        "lcom": 1,
        "length": 27,
        "vocabulary": 11,
        "volume": 93.4,
        "difficulty": 3.56,
        "effort": 332.75,
        "level": 0.28,
        "bugs": 0.03,
        "time": 18,
        "intelligentContent": 26.22,
        "number_operators": 8,
        "number_operands": 19,
        "number_operators_unique": 3,
        "number_operands_unique": 8,
        "cloc": 11,
        "loc": 32,
        "lloc": 21,
        "mi": 96.38,
        "mIwoC": 56.96,
        "commentWeight": 39.42,
        "kanDefect": 0.29,
        "relativeStructuralComplexity": 36,
        "relativeDataComplexity": 0.64,
        "relativeSystemComplexity": 36.64,
        "totalStructuralComplexity": 72,
        "totalDataComplexity": 1.29,
        "totalSystemComplexity": 73.29,
        "package": "Snicco\\Middleware\\HttpsOnly\\",
        "pageRank": 0,
        "afferentCoupling": 0,
        "efferentCoupling": 4,
        "instability": 1,
        "violations": {}
    }
]