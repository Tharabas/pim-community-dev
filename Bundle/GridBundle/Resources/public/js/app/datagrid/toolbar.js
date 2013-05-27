var Oro = Oro || {};
Oro.Datagrid = Oro.Datagrid || {};

/**
 * Datagrid toolbar widget
 *
 * @class   Oro.Datagrid.Toolbar
 * @extends Backbone.View
 */
Oro.Datagrid.Toolbar = Backbone.View.extend({

    /** @property */
    template:_.template(
        '<div class="grid-toolbar">' +
            '<div class="pull-left">' +
                '<div class="btn-group icons-holder" style="display: none;">' +
                    '<button class="btn"><i class="icon-edit hide-text">edit</i></button>' +
                    '<button class="btn"><i class="icon-copy hide-text">copy</i></button>' +
                    '<button class="btn"><i class="icon-trash hide-text">remove</i></button>' +
                '</div>' +
                '<div class="btn-group" style="display: none;">' +
                    '<button data-toggle="dropdown" class="btn dropdown-toggle">Status: <strong>All</strong><span class="caret"></span></button>' +
                    '<ul class="dropdown-menu">' +
                        '<li><a href="#">only short</a></li>' +
                        '<li><a href="#">this is long text for test</a></li>' +
                    '</ul>' +
                '</div>' +
            '</div>' +
            '<div class="pull-right">' +
            '<div class="actions-panel pull-right form-horizontal"></div>' +
            '<div class="page-size pull-right form-horizontal"></div>' +
            '</div>' +
            '<div class="pagination pagination-centered"></div>' +
        '</div>'
    ),

    /** @property */
    pagination: Oro.Datagrid.Pagination.Input,

    /** @property */
    pageSize: Oro.Datagrid.PageSize,

    /** @property */
    actionsPanel: Oro.Datagrid.ActionsPanel,

    /**
     * Initializer.
     *
     * @param {Object} options
     * @param {Backbone.Collection} options.collection
     * @param {Array} options.actions List of actions
     * @throws {TypeError} If "collection" is undefined
     */
    initialize: function (options) {
        options = options || {};

        if (!options.collection) {
            throw new TypeError("'collection' is required");
        }

        this.collection = options.collection;

        this.pagination = new this.pagination({
            collection: this.collection
        });

        this.pageSize = new this.pageSize({
            collection: this.collection
        });

        this.actionsPanel = new this.actionsPanel();
        if (options.actions) {
            this.actionsPanel.setActions(options.actions);
        }

        Backbone.View.prototype.initialize.call(this, options);
    },

    /**
     * Enable toolbar
     *
     * @return {*}
     */
    enable: function() {
        this.pagination.enable();
        this.pageSize.enable();
        this.actionsPanel.enable();
        return this;
    },

    /**
     * Disable toolbar
     *
     * @return {*}
     */
    disable: function() {
        this.pagination.disable();
        this.pageSize.disable();
        this.actionsPanel.disable();
        return this;
    },

    /**
     * Render toolbar with pager and other views
     */
    render: function() {
        this.$el.empty();
        this.$el.append(this.template());

        this.$('.pagination').replaceWith(this.pagination.render().$el);
        this.$('.page-size').append(this.pageSize.render().$el);
        this.$('.actions-panel').append(this.actionsPanel.render().$el);

        return this;
    }
});
