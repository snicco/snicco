var classes = [
    {
        "name": "Snicco\\Middleware\\Redirect\\Redirect",
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
                "name": "normalize",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 3,
        "nbMethods": 3,
        "nbMethodsPrivate": 1,
        "nbMethodsPublic": 2,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 9,
        "ccn": 7,
        "ccnMethodMax": 5,
        "externals": [
            "Snicco\\Component\\HttpRouting\\Middleware\\Middleware",
            "Psr\\Http\\Message\\ResponseInterface",
            "Snicco\\Component\\HttpRouting\\Http\\Psr7\\Request",
            "Snicco\\Component\\HttpRouting\\Middleware\\NextMiddleware",
            "InvalidArgumentException"
        ],
        "parents": [
            "Snicco\\Component\\HttpRouting\\Middleware\\Middleware"
        ],
        "lcom": 1,
        "length": 87,
        "vocabulary": 27,
        "volume": 413.68,
        "difficulty": 7.73,
        "effort": 3196.58,
        "level": 0.13,
        "bugs": 0.14,
        "time": 178,
        "intelligentContent": 53.53,
        "number_operators": 19,
        "number_operands": 68,
        "number_operators_unique": 5,
        "number_operands_unique": 22,
        "cloc": 15,
        "loc": 51,
        "lloc": 36,
        "mi": 84.03,
        "mIwoC": 46.79,
        "commentWeight": 37.24,
        "kanDefect": 0.82,
        "relativeStructuralComplexity": 25,
        "relativeDataComplexity": 0.89,
        "relativeSystemComplexity": 25.89,
        "totalStructuralComplexity": 75,
        "totalDataComplexity": 2.67,
        "totalSystemComplexity": 77.67,
        "package": "Snicco\\Middleware\\Redirect\\",
        "pageRank": 0,
        "afferentCoupling": 0,
        "efferentCoupling": 5,
        "instability": 1,
        "violations": {}
    }
]