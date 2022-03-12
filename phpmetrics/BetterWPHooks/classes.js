var classes = [
    {
        "name": "Snicco\\Component\\BetterWPHooks\\WPEventDispatcher",
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
                "name": "dispatch",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "remove",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "listen",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "subscribe",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 5,
        "nbMethods": 5,
        "nbMethodsPrivate": 0,
        "nbMethodsPublic": 5,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 10,
        "ccn": 6,
        "ccnMethodMax": 5,
        "externals": [
            "Snicco\\Component\\EventDispatcher\\EventDispatcher",
            "Snicco\\Component\\EventDispatcher\\EventDispatcher",
            "Snicco\\Component\\BetterWPHooks\\WPHookAPI",
            "Snicco\\Component\\BetterWPHooks\\WPHookAPI",
            "Snicco\\Component\\EventDispatcher\\GenericEvent"
        ],
        "parents": [],
        "lcom": 1,
        "length": 47,
        "vocabulary": 12,
        "volume": 168.49,
        "difficulty": 9.25,
        "effort": 1558.56,
        "level": 0.11,
        "bugs": 0.06,
        "time": 87,
        "intelligentContent": 18.22,
        "number_operators": 10,
        "number_operands": 37,
        "number_operators_unique": 4,
        "number_operands_unique": 8,
        "cloc": 9,
        "loc": 46,
        "lloc": 37,
        "mi": 81.04,
        "mIwoC": 49.39,
        "commentWeight": 31.64,
        "kanDefect": 0.29,
        "relativeStructuralComplexity": 64,
        "relativeDataComplexity": 0.51,
        "relativeSystemComplexity": 64.51,
        "totalStructuralComplexity": 320,
        "totalDataComplexity": 2.56,
        "totalSystemComplexity": 322.56,
        "package": "Snicco\\Component\\BetterWPHooks\\",
        "pageRank": 0,
        "afferentCoupling": 1,
        "efferentCoupling": 3,
        "instability": 0.75,
        "violations": {}
    },
    {
        "name": "Snicco\\Component\\BetterWPHooks\\EventFactory\\ParameterBasedHookFactory",
        "interface": false,
        "abstract": false,
        "final": true,
        "methods": [
            {
                "name": "make",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 1,
        "nbMethods": 1,
        "nbMethodsPrivate": 0,
        "nbMethodsPublic": 1,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 2,
        "ccn": 2,
        "ccnMethodMax": 2,
        "externals": [
            "Snicco\\Component\\BetterWPHooks\\EventFactory\\MappedHookFactory",
            "Snicco\\Component\\BetterWPHooks\\EventMapping\\MappedHook",
            "event_class",
            "Snicco\\Component\\BetterWPHooks\\Exception\\CantCreateMappedEvent"
        ],
        "parents": [],
        "lcom": 1,
        "length": 10,
        "vocabulary": 5,
        "volume": 23.22,
        "difficulty": 2.67,
        "effort": 61.92,
        "level": 0.38,
        "bugs": 0.01,
        "time": 3,
        "intelligentContent": 8.71,
        "number_operators": 2,
        "number_operands": 8,
        "number_operators_unique": 2,
        "number_operands_unique": 3,
        "cloc": 0,
        "loc": 12,
        "lloc": 12,
        "mi": 66.63,
        "mIwoC": 66.63,
        "commentWeight": 0,
        "kanDefect": 0.15,
        "relativeStructuralComplexity": 1,
        "relativeDataComplexity": 1.5,
        "relativeSystemComplexity": 2.5,
        "totalStructuralComplexity": 1,
        "totalDataComplexity": 1.5,
        "totalSystemComplexity": 2.5,
        "package": "Snicco\\Component\\BetterWPHooks\\EventFactory\\",
        "pageRank": 0,
        "afferentCoupling": 1,
        "efferentCoupling": 4,
        "instability": 0.8,
        "violations": {}
    },
    {
        "name": "Snicco\\Component\\BetterWPHooks\\Exception\\CantCreateMappedEvent",
        "interface": false,
        "abstract": false,
        "final": true,
        "methods": [
            {
                "name": "becauseTheEventCouldNotBeConstructorWithArgs",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 1,
        "nbMethods": 1,
        "nbMethodsPrivate": 0,
        "nbMethodsPublic": 1,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 1,
        "ccn": 1,
        "ccnMethodMax": 1,
        "externals": [
            "RuntimeException",
            "Throwable",
            "Snicco\\Component\\BetterWPHooks\\Exception\\CantCreateMappedEvent"
        ],
        "parents": [
            "RuntimeException"
        ],
        "lcom": 1,
        "length": 22,
        "vocabulary": 11,
        "volume": 76.11,
        "difficulty": 2,
        "effort": 152.21,
        "level": 0.5,
        "bugs": 0.03,
        "time": 8,
        "intelligentContent": 38.05,
        "number_operators": 4,
        "number_operands": 18,
        "number_operators_unique": 2,
        "number_operands_unique": 9,
        "cloc": 0,
        "loc": 11,
        "lloc": 11,
        "mi": 63.97,
        "mIwoC": 63.97,
        "commentWeight": 0,
        "kanDefect": 0.15,
        "relativeStructuralComplexity": 1,
        "relativeDataComplexity": 2,
        "relativeSystemComplexity": 3,
        "totalStructuralComplexity": 1,
        "totalDataComplexity": 2,
        "totalSystemComplexity": 3,
        "package": "Snicco\\Component\\BetterWPHooks\\Exception\\",
        "pageRank": 0,
        "afferentCoupling": 3,
        "efferentCoupling": 4,
        "instability": 0.57,
        "violations": {}
    },
    {
        "name": "Snicco\\Component\\BetterWPHooks\\EventMapping\\EventMapper",
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
                "name": "map",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "mapFirst",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "mapLast",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "validate",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "mapValidated",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "dispatchMappedAction",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "dispatchMappedFilter",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "ensureFirst",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "ensureLast",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 10,
        "nbMethods": 10,
        "nbMethodsPrivate": 6,
        "nbMethodsPublic": 4,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 32,
        "ccn": 23,
        "ccnMethodMax": 6,
        "externals": [
            "Snicco\\Component\\EventDispatcher\\EventDispatcher",
            "Snicco\\Component\\BetterWPHooks\\WPHookAPI",
            "Snicco\\Component\\BetterWPHooks\\EventFactory\\ParameterBasedHookFactory",
            "LogicException",
            "LogicException",
            "InvalidArgumentException",
            "InvalidArgumentException",
            "RuntimeException",
            "LogicException",
            "LogicException"
        ],
        "parents": [],
        "lcom": 1,
        "length": 226,
        "vocabulary": 42,
        "volume": 1218.66,
        "difficulty": 27.19,
        "effort": 33132.42,
        "level": 0.04,
        "bugs": 0.41,
        "time": 1841,
        "intelligentContent": 44.82,
        "number_operators": 52,
        "number_operands": 174,
        "number_operators_unique": 10,
        "number_operands_unique": 32,
        "cloc": 66,
        "loc": 187,
        "lloc": 121,
        "mi": 69.66,
        "mIwoC": 29.87,
        "commentWeight": 39.79,
        "kanDefect": 1.13,
        "relativeStructuralComplexity": 196,
        "relativeDataComplexity": 0.81,
        "relativeSystemComplexity": 196.81,
        "totalStructuralComplexity": 1960,
        "totalDataComplexity": 8.07,
        "totalSystemComplexity": 1968.07,
        "package": "Snicco\\Component\\BetterWPHooks\\EventMapping\\",
        "pageRank": 0,
        "afferentCoupling": 1,
        "efferentCoupling": 6,
        "instability": 0.86,
        "violations": {}
    },
    {
        "name": "Snicco\\Component\\BetterWPHooks\\WPHookAPI",
        "interface": false,
        "abstract": false,
        "final": true,
        "methods": [
            {
                "name": "currentFilter",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "getHook",
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
        "wmc": 6,
        "ccn": 5,
        "ccnMethodMax": 4,
        "externals": [
            "Snicco\\Component\\BetterWPAPI\\BetterWPAPI",
            "RuntimeException"
        ],
        "parents": [
            "Snicco\\Component\\BetterWPAPI\\BetterWPAPI"
        ],
        "lcom": 2,
        "length": 27,
        "vocabulary": 13,
        "volume": 99.91,
        "difficulty": 5,
        "effort": 499.56,
        "level": 0.2,
        "bugs": 0.03,
        "time": 28,
        "intelligentContent": 19.98,
        "number_operators": 11,
        "number_operands": 16,
        "number_operators_unique": 5,
        "number_operands_unique": 8,
        "cloc": 14,
        "loc": 37,
        "lloc": 23,
        "mi": 96.38,
        "mIwoC": 55.62,
        "commentWeight": 40.76,
        "kanDefect": 0.36,
        "relativeStructuralComplexity": 0,
        "relativeDataComplexity": 4.5,
        "relativeSystemComplexity": 4.5,
        "totalStructuralComplexity": 0,
        "totalDataComplexity": 9,
        "totalSystemComplexity": 9,
        "package": "Snicco\\Component\\BetterWPHooks\\",
        "pageRank": 0,
        "afferentCoupling": 3,
        "efferentCoupling": 2,
        "instability": 0.4,
        "violations": {}
    }
]