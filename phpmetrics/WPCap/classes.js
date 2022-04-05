var classes = [
    {
        "name": "Snicco\\Middleware\\WPCap\\AuthorizeWPCap",
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
                "name": "process",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 2,
        "nbMethods": 2,
        "nbMethodsPrivate": 0,
        "nbMethodsPublic": 2,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 5,
        "ccn": 4,
        "ccnMethodMax": 3,
        "externals": [
            "Psr\\Http\\Server\\MiddlewareInterface",
            "Snicco\\Component\\BetterWPAPI\\BetterWPAPI",
            "Snicco\\Component\\BetterWPAPI\\BetterWPAPI",
            "Psr\\Http\\Message\\ResponseInterface",
            "Psr\\Http\\Message\\ServerRequestInterface",
            "Psr\\Http\\Server\\RequestHandlerInterface",
            "Snicco\\Component\\Psr7ErrorHandler\\HttpException"
        ],
        "parents": [],
        "implements": [
            "Psr\\Http\\Server\\MiddlewareInterface"
        ],
        "lcom": 1,
        "length": 32,
        "vocabulary": 12,
        "volume": 114.72,
        "difficulty": 4,
        "effort": 458.88,
        "level": 0.25,
        "bugs": 0.04,
        "time": 25,
        "intelligentContent": 28.68,
        "number_operators": 8,
        "number_operands": 24,
        "number_operators_unique": 3,
        "number_operands_unique": 9,
        "cloc": 3,
        "loc": 27,
        "lloc": 24,
        "mi": 79.62,
        "mIwoC": 54.93,
        "commentWeight": 24.69,
        "kanDefect": 0.29,
        "relativeStructuralComplexity": 16,
        "relativeDataComplexity": 0.7,
        "relativeSystemComplexity": 16.7,
        "totalStructuralComplexity": 32,
        "totalDataComplexity": 1.4,
        "totalSystemComplexity": 33.4,
        "package": "Snicco\\Middleware\\WPCap\\",
        "pageRank": 0,
        "afferentCoupling": 0,
        "efferentCoupling": 6,
        "instability": 1,
        "violations": {}
    }
]