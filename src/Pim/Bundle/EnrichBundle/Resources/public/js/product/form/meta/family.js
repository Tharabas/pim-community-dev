 'use strict';
/**
 * Family extension
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @author    Filips Alpe <filips@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
define(
    ['underscore', 'pim/form', 'text!pim/template/product/meta/family'],
    function (_, BaseForm, template) {
        return BaseForm.extend({
            tagName: 'span',
            className: 'family',
            template: _.template(template),
            configure: function () {
                this.listenTo(this.getRoot().model, 'change:family', this.render);

                return BaseForm.prototype.configure.apply(this, arguments);
            },
            render: function () {
                if (!this.configured) {
                    return this;
                }

                this.$el.html(
                    this.template({
                        product: this.getData()
                    })
                );

                return BaseForm.prototype.render.apply(this, arguments);
            }
        });
    }
);
