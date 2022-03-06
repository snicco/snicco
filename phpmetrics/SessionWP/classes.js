var classes = [
    {
        "name": "Snicco\\Bridge\\SessionWP\\WPDBSessionDriver",
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
                "name": "read",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "write",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "destroy",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "gc",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "touch",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "destroyAll",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "destroyAllForUserId",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "destroyAllForUserIdExcept",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "getAllForUserId",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "createTable",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "exists",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "instantiate",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 13,
        "nbMethods": 13,
        "nbMethodsPrivate": 2,
        "nbMethodsPublic": 11,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 20,
        "ccn": 8,
        "ccnMethodMax": 3,
        "externals": [
            "Snicco\\Component\\Session\\Driver\\UserSessionsDriver",
            "Snicco\\Component\\BetterWPDB\\BetterWPDB",
            "Snicco\\Component\\TestableClock\\Clock",
            "Snicco\\Component\\TestableClock\\SystemClock",
            "Snicco\\Component\\Session\\ValueObject\\SerializedSession",
            "Snicco\\Component\\Session\\Exception\\BadSessionID",
            "Snicco\\Component\\Session\\ValueObject\\SerializedSession",
            "Snicco\\Component\\Session\\Exception\\BadSessionID",
            "Snicco\\Component\\Session\\ValueObject\\SerializedSession",
            "Snicco\\Component\\Session\\ValueObject\\SerializedSession"
        ],
        "parents": [],
        "lcom": 1,
        "length": 169,
        "vocabulary": 46,
        "volume": 933.48,
        "difficulty": 13.01,
        "effort": 12147.23,
        "level": 0.08,
        "bugs": 0.31,
        "time": 675,
        "intelligentContent": 71.74,
        "number_operators": 24,
        "number_operands": 145,
        "number_operators_unique": 7,
        "number_operands_unique": 39,
        "cloc": 25,
        "loc": 106,
        "lloc": 81,
        "mi": 70.66,
        "mIwoC": 36.5,
        "commentWeight": 34.17,
        "kanDefect": 0.45,
        "relativeStructuralComplexity": 256,
        "relativeDataComplexity": 0.31,
        "relativeSystemComplexity": 256.31,
        "totalStructuralComplexity": 3328,
        "totalDataComplexity": 4,
        "totalSystemComplexity": 3332,
        "package": "Snicco\\Bridge\\SessionWP\\",
        "pageRank": 0,
        "afferentCoupling": 1,
        "efferentCoupling": 6,
        "instability": 0.86,
        "violations": {}
    },
    {
        "name": "Snicco\\Bridge\\SessionWP\\WPObjectCacheDriver",
        "interface": false,
        "abstract": false,
        "final": true,
        "methods": [
            {
                "name": "__construct",
                "role": "setter",
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "read",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "write",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "destroy",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "gc",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "touch",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 6,
        "nbMethods": 5,
        "nbMethodsPrivate": 0,
        "nbMethodsPublic": 5,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 1,
        "wmc": 6,
        "ccn": 1,
        "ccnMethodMax": 1,
        "externals": [
            "Snicco\\Component\\Session\\Driver\\SessionDriver",
            "Snicco\\Bridge\\SessionPsr16\\Psr16SessionDriver",
            "Snicco\\Component\\Session\\ValueObject\\SerializedSession",
            "Snicco\\Component\\Session\\ValueObject\\SerializedSession"
        ],
        "parents": [],
        "lcom": 1,
        "length": 24,
        "vocabulary": 9,
        "volume": 76.08,
        "difficulty": 3.14,
        "effort": 239.1,
        "level": 0.32,
        "bugs": 0.03,
        "time": 13,
        "intelligentContent": 24.21,
        "number_operators": 2,
        "number_operands": 22,
        "number_operators_unique": 2,
        "number_operands_unique": 7,
        "cloc": 0,
        "loc": 29,
        "lloc": 29,
        "mi": 54.79,
        "mIwoC": 54.79,
        "commentWeight": 0,
        "kanDefect": 0.15,
        "relativeStructuralComplexity": 25,
        "relativeDataComplexity": 0.39,
        "relativeSystemComplexity": 25.39,
        "totalStructuralComplexity": 150,
        "totalDataComplexity": 2.33,
        "totalSystemComplexity": 152.33,
        "package": "Snicco\\Bridge\\SessionWP\\",
        "pageRank": 0,
        "afferentCoupling": 1,
        "efferentCoupling": 3,
        "instability": 0.75,
        "violations": {}
    }
]