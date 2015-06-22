'use strict';
/**
 * Textarea field
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @author    Filips Alpe <filips@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
define(
    [
        'pim/field',
        'underscore',
        'text!pim/template/product/field/textarea',
        'summernote'
    ],
    function (
        Field,
        _,
        fieldTemplate
    ) {
        return Field.extend({
            fieldTemplate: _.template(fieldTemplate),
            fieldType: 'textarea',
            events: {
                'change .field-input:first textarea': 'updateModel'
            },
            renderInput: function (context) {
                return this.fieldTemplate(context);
            },
            postRender: function () {
                if (this.attribute.wysiwyg_enabled) {
                    this.$('textarea').summernote({
                        disableResizeEditor: true,
                        height: 200,
                        iconPrefix: 'icon-',
                        toolbar: [
                            ['font', ['bold', 'italic', 'underline', 'clear']],
                            ['para', ['ul', 'ol']],
                            ['insert', ['link']],
                            ['view', ['codeview']]
                        ]
                    }).on('summernote.blur', _.bind(this.updateModel, this));
                }
            },
            updateModel: function () {
                var data;
                if (this.attribute.wysiwyg_enabled) {
                    data = this.$('.field-input:first textarea:first').code();
                } else {
                    data = this.$('.field-input:first textarea:first').val();
                }
                data = '' === data ? this.attribute.empty_value : data;

                this.setCurrentValue(data);
            },
            setFocus: function () {
                if (this.attribute.wysiwyg_enabled) {
                    this.$('.field-input:first textarea').summernote('focus');
                } else {
                    this.$('.field-input:first textarea').focus();
                }
            }
        });
    }
);
