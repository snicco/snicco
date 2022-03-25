var classes = [
    {
        "name": "Snicco\\Middleware\\NoRobots\\NoRobots",
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
        "wmc": 5,
        "ccn": 4,
        "ccnMethodMax": 4,
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
        "length": 35,
        "vocabulary": 10,
        "volume": 116.27,
        "difficulty": 5.14,
        "effort": 597.95,
        "level": 0.19,
        "bugs": 0.04,
        "time": 33,
        "intelligentContent": 22.61,
        "number_operators": 11,
        "number_operands": 24,
        "number_operators_unique": 3,
        "number_operands_unique": 7,
        "cloc": 0,
        "loc": 27,
        "lloc": 27,
        "mi": 53.78,
        "mIwoC": 53.78,
        "commentWeight": 0,
        "kanDefect": 0.36,
        "relativeStructuralComplexity": 9,
        "relativeDataComplexity": 0.88,
        "relativeSystemComplexity": 9.88,
        "totalStructuralComplexity": 18,
        "totalDataComplexity": 1.75,
        "totalSystemComplexity": 19.75,
        "package": "Snicco\\Middleware\\NoRobots\\",
        "pageRank": 0,
        "afferentCoupling": 0,
        "efferentCoupling": 4,
        "instability": 1,
        "violations": {}
    }
]