var classes = [
    {
        "name": "Snicco\\Middleware\\TrailingSlash\\TrailingSlash",
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
        "wmc": 6,
        "ccn": 5,
        "ccnMethodMax": 5,
        "externals": [
            "Snicco\\Component\\HttpRouting\\Middleware\\Middleware",
            "Psr\\Http\\Message\\ResponseInterface",
            "Snicco\\Component\\HttpRouting\\Http\\Psr7\\Request",
            "Snicco\\Component\\HttpRouting\\Middleware\\NextMiddleware",
            "Snicco\\Component\\StrArr\\Str",
            "Snicco\\Component\\StrArr\\Str"
        ],
        "parents": [
            "Snicco\\Component\\HttpRouting\\Middleware\\Middleware"
        ],
        "implements": [],
        "lcom": 1,
        "length": 40,
        "vocabulary": 14,
        "volume": 152.29,
        "difficulty": 8.06,
        "effort": 1226.81,
        "level": 0.12,
        "bugs": 0.05,
        "time": 68,
        "intelligentContent": 18.91,
        "number_operators": 11,
        "number_operands": 29,
        "number_operators_unique": 5,
        "number_operands_unique": 9,
        "cloc": 4,
        "loc": 26,
        "lloc": 22,
        "mi": 83.31,
        "mIwoC": 54.76,
        "commentWeight": 28.55,
        "kanDefect": 0.29,
        "relativeStructuralComplexity": 16,
        "relativeDataComplexity": 0.9,
        "relativeSystemComplexity": 16.9,
        "totalStructuralComplexity": 32,
        "totalDataComplexity": 1.8,
        "totalSystemComplexity": 33.8,
        "package": "Snicco\\Middleware\\TrailingSlash\\",
        "pageRank": 0,
        "afferentCoupling": 0,
        "efferentCoupling": 5,
        "instability": 1,
        "violations": {}
    }
]