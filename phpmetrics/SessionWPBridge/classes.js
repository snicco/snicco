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
                "name": "destroyAllForAllUsers",
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
            "Snicco\\Component\\Session\\Exception\\UnknownSessionSelector",
            "Snicco\\Component\\Session\\ValueObject\\SerializedSession",
            "Snicco\\Component\\Session\\Exception\\UnknownSessionSelector",
            "Snicco\\Component\\Session\\ValueObject\\SerializedSession",
            "Snicco\\Component\\Session\\ValueObject\\SerializedSession"
        ],
        "parents": [],
        "implements": [
            "Snicco\\Component\\Session\\Driver\\UserSessionsDriver"
        ],
        "lcom": 1,
        "length": 162,
        "vocabulary": 40,
        "volume": 862.15,
        "difficulty": 12.09,
        "effort": 10421.9,
        "level": 0.08,
        "bugs": 0.29,
        "time": 579,
        "intelligentContent": 71.32,
        "number_operators": 25,
        "number_operands": 137,
        "number_operators_unique": 6,
        "number_operands_unique": 34,
        "cloc": 25,
        "loc": 108,
        "lloc": 83,
        "mi": 70.42,
        "mIwoC": 36.51,
        "commentWeight": 33.91,
        "kanDefect": 0.52,
        "relativeStructuralComplexity": 256,
        "relativeDataComplexity": 0.43,
        "relativeSystemComplexity": 256.43,
        "totalStructuralComplexity": 3328,
        "totalDataComplexity": 5.53,
        "totalSystemComplexity": 3333.53,
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
        "wmc": 5,
        "ccn": 1,
        "ccnMethodMax": 1,
        "externals": [
            "Snicco\\Component\\Session\\Driver\\SessionDriver",
            "Snicco\\Bridge\\SessionPsr16\\Psr16SessionDriver",
            "Snicco\\Component\\Session\\ValueObject\\SerializedSession",
            "Snicco\\Component\\Session\\ValueObject\\SerializedSession"
        ],
        "parents": [],
        "implements": [
            "Snicco\\Component\\Session\\Driver\\SessionDriver"
        ],
        "lcom": 2,
        "length": 22,
        "vocabulary": 8,
        "volume": 66,
        "difficulty": 3.33,
        "effort": 220,
        "level": 0.3,
        "bugs": 0.02,
        "time": 12,
        "intelligentContent": 19.8,
        "number_operators": 2,
        "number_operands": 20,
        "number_operators_unique": 2,
        "number_operands_unique": 6,
        "cloc": 0,
        "loc": 28,
        "lloc": 28,
        "mi": 55.56,
        "mIwoC": 55.56,
        "commentWeight": 0,
        "kanDefect": 0.15,
        "relativeStructuralComplexity": 16,
        "relativeDataComplexity": 0.47,
        "relativeSystemComplexity": 16.47,
        "totalStructuralComplexity": 96,
        "totalDataComplexity": 2.8,
        "totalSystemComplexity": 98.8,
        "package": "Snicco\\Bridge\\SessionWP\\",
        "pageRank": 0,
        "afferentCoupling": 1,
        "efferentCoupling": 3,
        "instability": 0.75,
        "violations": {}
    }
]