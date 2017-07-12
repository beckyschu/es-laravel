var Vue = require('vue')
var _   = require('underscore')

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['auth'],
    data: function () {
        return {
            user: _.extend({}, this.auth.user),
            image: null,
            detailErrors: [],
            isUpdatingDetails: false,
            userStatuses: require('../../helpers/basicStatuses'),
            passwordErrors: [],
            isUpdatingPassword: false,
            password: null,
            passwordConfirm: null,
        }
    },
    created: function ()
    {
        // Update subheader title
        this.$root.bus.$emit('subheader.updateTitle', 'My Account');
    },
    methods: {

        /**
         * Update the details for this user.
         */
        updateDetails: function ()
        {
            //Already saving
            if (this.isUpdatingDetails) return

            //Set saving flag
            this.isUpdatingDetails = true

            //Pack our payload
            var payload = {
                data: {
                    type: 'users',
                    id: this.id,
                    attributes: {
                        first_name: this.user.first_name,
                        last_name:  this.user.last_name,
                        email:      this.user.email,
                    }
                }
            };

            //Send request to API
            this.$http.patch('users/'+this.user.id, payload)
                .then(function (response) {

                    //Copy details across to auth
                    this.auth.user = _.extend(this.auth.user, this.user);

                    //We have a file to upload
                    if (this.image)
                    {
                        var formData = new FormData
                        formData.append('image', this.image)

                        this.$http.post('users/'+this.user.id+'/image', formData)
                            .then(function (response) {

                                //Dispatch success message
                                this.$root.bus.$emit('success', 'User details updated successfully.')

                                //Clear saving flag
                                this.isUpdatingDetails = false

                                //Dispatch image updated event
                                this.$root.bus.$emit('me.imageUpdated');

                            }, function (response) {

                                //Loop errors and add to collection
                                _.each(response.data.errors, function (error) {
                                    this.detailErrors.push(error.detail)
                                }.bind(this))

                                //Clear saving flag
                                this.isUpdatingDetails = false

                            })
                    }

                    //No file to upload
                    else {

                        //Dispatch success message
                        this.$root.bus.$emit('success', 'User details updated successfully.')

                        //Clear saving flag
                        this.isUpdatingDetails = false

                    }

                }, function (response) {

                    //Loop errors and add to collection
                    _.each(response.data.errors, function (error) {
                        this.detailErrors.push(error.detail)
                    }.bind(this));

                    //Clear saving flag
                    this.isUpdatingDetails = false

                });
        },

        /**
         * Update password for this user.
         */
        updatePassword: function ()
        {
            //Already saving
            if (this.isUpdatingPassword || ! this.password) return

            //Set saving flag
            this.isUpdatingPassword = true

            //Pack our payload
            var payload = {
                data: {
                    type: 'users',
                    id: this.id,
                    attributes: {
                        password: this.password
                    }
                }
            };

            //Send request to API
            this.$http.patch('users/'+this.user.id, payload)
                .then(function (response) {

                    //Dispatch success message
                    this.$root.bus.$emit('success', 'Password updated successfully.')

                    //Clear saving flag
                    this.isUpdatingPassword = false

                    //Clear input
                    this.password = null
                    this.passwordConfirm = null

                }, function (response) {

                    //Loop errors and add to collection
                    _.each(response.data.errors, function (error) {
                        this.passwordErrors.push(error.detail)
                    }.bind(this));

                    //Clear saving flag
                    this.isUpdatingDetails = false

                });
        },

        /**
         * Select a new image for upload.
         */
        selectImage: function (e)
        {
            this.image = e.target.files[0]
        }

    }
})
