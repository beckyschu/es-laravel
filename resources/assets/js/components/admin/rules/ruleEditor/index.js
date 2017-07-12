var Vue = require('vue')
var _   = require('underscore')

module.exports = Vue.extend({
    name: 'RuleEditor',
    template: require('./template.html'),
    props: ['rule'],
    data: function() {
        return {
            multiComparison: 'and',
            lines: [],
            newLine: {
                operation: null,
                values: {
                    one: null,
                    two: null,
                },
                discreteValues: {
                    one: null,
                    two: null,
                }
            },
            options: {
                operations: [
                    {id: null, label: 'Operation'},
                    {id: '!', label: '!'},
                    {id: '==', label: '=='},
                    {id: '!=', label: '!='},
                    {id: 'in', label: 'in'},
                ],
                value: [
                    {id: null, label: 'Value'},
                    {id: 'discovery.asset_id', label: 'discovery.asset_id'},
                    {id: 'discovery.account_id', label: 'discovery.account_id'},
                    {id: 'discovery.seller_id', label: 'discovery.seller_id'},
                    {id: 'discovery.platform', label: 'discovery.platform'},
                    {id: 'discovery.title', label: 'discovery.title'},
                    {id: 'discovery.category', label: 'discovery.category'},
                    {id: 'discovery.keyword', label: 'discovery.keyword'},
                    {id: 'discovery.origin', label: 'discovery.origin'},
                    {id: 'discovery.price', label: 'discovery.price'},
                    {id: 'discovery.url', label: 'discovery.url'},
                    {id: 'discovery.qty_available', label: 'discovery.qty_available'},
                    {id: 'discovery.qty_sold', label: 'discovery.qty_sold'},
                    {id: 'discovery.status', label: 'discovery.status'},
                    {id: 'discovery.status_updated_at', label: 'discovery.status_updated_at'},
                    {id: 'discovery.status_updated_at_days', label: 'discovery.status_updated_at_days'},
                    {id: 'seller.name', label: 'seller.name'},
                    {id: 'seller.last_seen_at', label: 'seller.last_seen_at'},
                    {id: 'seller.discovery_count', label: 'seller.discovery_count'},
                    {id: 'discrete', label: 'Discrete Value'},
                ]
            }
        }
    },
    created: function()
    {
        // Parse the rule on create
        this.parseRule()

        // Parse for rule whenever changes seen
        this.$watch(function () {
            return JSON.stringify(this.lines) + this.multiComparison
        }, function (newVal, oldVal) {
            this.generateRule()
        });
    },
    methods: {

        /**
         * Parse the propped rule object.
         */
        parseRule: function ()
        {
            // No rule provided
            if (! this.rule.rule) return

            // Let the rule for easy access
            let rule = this.rule.rule

            // Find the multi comparison
            if (rule.and) this.multiComparison = 'and'
            if (rule.or)  this.multiComparison = 'or'

            // Rule is not bound by multi comparison
            if (! rule.and && ! rule.or)
            {
                let operation = _.keys(rule)[0]
                this.lines.push({
                    operation: operation,
                    values: {
                        one: this.parseLogicValue(rule[operation][0]),
                        two: this.parseLogicValue(rule[operation][1]),
                    },
                    discreteValues: {
                        one: 'discrete' == this.parseLogicValue(rule[operation][0]) ? this.parseLogicDiscrete(rule[operation][0]) : null,
                        two: 'discrete' == this.parseLogicValue(rule[operation][1]) ? this.parseLogicDiscrete(rule[operation][1]) : null,
                    }
                })
            }

            // Rule is bound by multi comparison
            else {
                _.each(rule[this.multiComparison], function (subRule)
                {
                    let operation = _.keys(subRule)[0]
                    this.lines.push({
                        operation: operation,
                        values: {
                            one: this.parseLogicValue(subRule[operation][0]),
                            two: this.parseLogicValue(subRule[operation][1])
                        },
                        discreteValues: {
                            one: 'discrete' == this.parseLogicValue(subRule[operation][0]) ? this.parseLogicDiscrete(subRule[operation][0]) : null,
                            two: 'discrete' == this.parseLogicValue(subRule[operation][1]) ? this.parseLogicDiscrete(subRule[operation][1]) : null,
                        }
                    })
                }.bind(this))
            }

            console.log(this.lines)
        },

        /**
         * Parse a logic value as a variable name or "discrete"
         */
        parseLogicValue (value) {

            // Value is an array, return discrete
            if (_.isArray(value)) return 'discrete'

            // Value is a variable object
            if (_.isObject(value)) return value.var

            // Return discrete as fallback
            return 'discrete'

        },

        /**
         * Join discrete array if required
         */
        parseLogicDiscrete (value) {
            if (_.isArray(value)) return value.join(',')
            return value
        },

        /**
         * Generate a rule object from the stored lines.
         */
        generateRule: function ()
        {
            // First, create an array of rule objects
            let rulesArr = _.map(this.lines, function (line)
            {
                // Generate value one
                let ruleValueOne = null
                if ('discrete' == line.values.one) {
                    ruleValueOne = line.discreteValues.one.includes(',')
                        ? line.discreteValues.one.split(',')
                        : line.discreteValues.one
                } else {
                    ruleValueOne = {var: line.values.one}
                }

                // Generate value two
                let ruleValueTwo = null
                if (line.values.two) {
                    if ('discrete' == line.values.two) {
                        ruleValueTwo = line.discreteValues.two.includes(',')
                            ? line.discreteValues.two.split(',')
                            : line.discreteValues.two
                    } else {
                        ruleValueTwo = {var: line.values.two}
                    }
                }

                // Create an empty rule object
                let rule = {}

                // Assign to operation key
                if (ruleValueTwo) {
                    rule[line.operation] = [ruleValueOne, ruleValueTwo]
                } else {
                    rule[line.operation] = ruleValueOne
                }

                // Return new rule object
                return rule
            })

            // Assign rules array
            let rule = {}
            if (1 == rulesArr.length) {
                rule = rulesArr[0]
            } else {
                rule[this.multiComparison] = rulesArr
            }

            console.log(rule)

            // Update parent object
            this.rule.rule = rule
        },

        /**
         * Add new operation line.
         */
        addNewLine: function ()
        {
            // No operation provided
            if (! this.newLine.operation) return

            // Push new line
            this.lines.push(JSON.parse(JSON.stringify(this.newLine)))

            // Reset newline fields
            this.newLine.operation          = null
            this.newLine.values.one         = null
            this.newLine.values.two         = null
            this.newLine.discreteValues.one = null
            this.newLine.discreteValues.two = null
        },

        /**
         * Whether or not to show a second value for the given operation.
         */
        showSecondValue: function (operation)
        {
            return '!' !== operation;
        },

        /**
         * Remove the provided line.
         */
        removeLine: function (index)
        {
            this.lines.splice(index, 1)
        }

    }
})
