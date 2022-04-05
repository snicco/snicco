var classes = [
    {
        "name": "Snicco\\Middleware\\WPAuth\\AuthenticateWPUser",
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
        "ccnMethodMax": 2,
        "externals": [
            "Snicco\\Component\\HttpRouting\\Middleware\\Middleware",
            "Snicco\\Component\\BetterWPAPI\\BetterWPAPI",
            "Snicco\\Component\\BetterWPAPI\\BetterWPAPI",
            "Psr\\Http\\Message\\ResponseInterface",
            "Snicco\\Component\\HttpRouting\\Http\\Psr7\\Request",
            "Snicco\\Component\\HttpRouting\\Middleware\\NextMiddleware",
            "Snicco\\Component\\Psr7ErrorHandler\\HttpException"
        ],
        "parents": [
            "Snicco\\Component\\HttpRouting\\Middleware\\Middleware"
        ],
        "implements": [],
        "lcom": 1,
        "length": 14,
        "vocabulary": 9,
        "volume": 44.38,
        "difficulty": 2.75,
        "effort": 122.04,
        "level": 0.36,
        "bugs": 0.01,
        "time": 7,
        "intelligentContent": 16.14,
        "number_operators": 3,
        "number_operands": 11,
        "number_operators_unique": 3,
        "number_operands_unique": 6,
        "cloc": 0,
        "loc": 16,
        "lloc": 16,
        "mi": 61.8,
        "mIwoC": 61.8,
        "commentWeight": 0,
        "kanDefect": 0.22,
        "relativeStructuralComplexity": 9,
        "relativeDataComplexity": 0.63,
        "relativeSystemComplexity": 9.63,
        "totalStructuralComplexity": 18,
        "totalDataComplexity": 1.25,
        "totalSystemComplexity": 19.25,
        "package": "Snicco\\Middleware\\WPAuth\\",
        "pageRank": 0,
        "afferentCoupling": 0,
        "efferentCoupling": 6,
        "instability": 1,
        "violations": {}
    }
]