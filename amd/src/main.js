// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Javascript to initialise the block.
 *
 * @module    mod_dataview/main
 * @copyright 2020 David Herney @ BambuCo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/modal_factory', 'core/modal_events', 'core/templates', 'core/notification', 'core/ajax'],
function ($, ModalFactory, ModalEvents, Templates, Notification, Ajax) {

    var wwwroot = M.cfg.wwwroot;
    var createmessagemodal = null;
    var $currentopenlink = null;
    var $recordsboard = null;
    var $createmessage = null;

    var loadrecord = function (record, template) {

        var re, newvalue;

        for (var i in record) {
            if (record.hasOwnProperty(i)) {
                re = new RegExp('\\[\\[' + i + '\\]\\]', "g");
                newvalue = record[i] ? record[i] : '';
                template = template.replace(re, newvalue);
            }
        }

        return template;
    };

    /**
     * Initialise all.
     *
     * @param {int} dataviewid The id of the dataview.
     */
    var init = function (dataviewid) {

        $recordsboard = $('.dataview-board .records');
        $createmessage = $('#dataview-tpl-createmessage');

        $('.filter-box .one-filter').each(function() {
            var $_this = $(this);
            $_this.find('.filter-head').on('click', function() {
                $_this.toggleClass('opened');
            });

        });

        // Modal to display data.
        ModalFactory.create({
            body: $createmessage.html(),
            type: ModalFactory.types.SAVE_CANCEL
        })
        .then(function(modal) {

            createmessagemodal = modal;

            modal.getModal().addClass('mod_dataview-message-modal');
            modal.setSaveButtonText(M.str.mod_dataview.goto);

            // Confirmation only.
            modal.getRoot().on(ModalEvents.save, function() {
                if ($currentopenlink) {
                    $("<a>").prop({
                        target: "_blank",
                        href: $currentopenlink.attr('href')
                        })[0].click();
                }
            });

            // Confirming, closing, or cancelling will destroy the modal and return focus to the trigger element.
            modal.getRoot().on(ModalEvents.hidden, function() {
                $currentopenlink = null;
            });
        });

        $('.filter-box .search-btn').on('click', function () {

            $recordsboard.empty().addClass('loading');

            var q = $('#fulltext-query').val();
            var sortfield = 0;
            var sortdir = 'DESC';
            if ($('#sortby').length > 0) {
                sortfield = $('#sortby').val();
                sortdir = $('#sortdirection').val();
            }
            var limit = parseInt($('#recordsperpage').val());
            get_records(dataviewid, q, [], sortfield, sortdir, limit);

        });

        $('.filter-box .filter-btn').on('click', function () {

            var filters = [];

            $('.one-filter').each(function() {
                var $one = $(this);
                $one.find('input[type="checkbox"]:checked:enabled').each(function() {
                    var $control = $(this);

                    var val = $.trim($control.val());
                    if (val != '') {
                        var filter = {
                            "key": $control.attr('name'),
                            "value": val
                        };

                        filters.push(filter);
                    }
                });

                $one.find('select').each(function() {

                    var $control = $(this);

                    $control.find('option:selected').each(function() {
                        var val = $.trim($(this).val());
                        if (val != '') {
                            var filter = {
                                "key": $control.attr('name'),
                                "value": val
                            };

                            if (filter.value != '') {
                                filters.push(filter);
                            }
                        }
                    });

                });

                $one.find('input[type="text"]').each(function() {

                    var $control = $(this);
                    var val = $.trim($control.val());
                    if (val != '') {
                        var filter = {
                            "key": $control.attr('name'),
                            "value": val
                        };

                        filters.push(filter);
                    }
                });
            });

            $recordsboard.empty().addClass('loading');

            // Clear special characters used by original data module.
            filters.forEach(element => {
                element.key = element.key.replace('f_', '');
                element.key = element.key.replace('[]', '');
            });

            var sortfield = 0;
            var sortdir = 'DESC';
            if ($('#sortby').length > 0) {
                sortfield = $('#sortby').val();
                sortdir = $('#sortdirection').val();
            }
            var limit = parseInt($('#recordsperpage').val());
            get_records(dataviewid, '', filters, sortfield, sortdir, limit);

        });

    };

    /**
     * Get records from the dataview.
     *
     * @param {int} dataviewid
     * @param {string} q
     * @param {object} filters
     * @param {string} sort
     * @param {string} dir ASC or DESC
     * @param {int} limit
     */
    var get_records = function (dataviewid, q, filters, sort = 0, dir = 'DESC', limit = 0) {

        var $listtemplate = $('#dataview-tpl-itemlist');
        var $singletemplate = $('#dataview-tpl-itemsingle');

        limit = limit || 0;

        Ajax.call([{
            methodname: 'mod_dataview_query',
            args: { 'id': dataviewid, 'q': q, 'filters': filters, 'sort': sort, 'dir': dir, 'limit': limit },
            done: function (data) {
                $('.dataview-board .records').removeClass('loading');

                data.forEach(element => {
                    var record = JSON.parse(element);
                    var $content = $(loadrecord(record, $listtemplate.html()));
                    $content.find('[data-operation="viewdetail"]').on('click', function() {
                        var $open = $(this);
                        var modalresource = $open.data('modal');

                        if (modalresource) {
                            modalresource.show();
                            return;
                        }

                        var tpl = $singletemplate.html();
                        var detailview;

                        if (tpl.trim() != '') {
                            detailview = loadrecord(record, tpl);
                        } else {
                            record.wwwroot = wwwroot;
                            detailview = Templates.render('mod_dataview/detail', record)
                            .then(function(html) {
                                    var $html = $(html);
                                    $html.find('[data-operation="confirmgo"]').on('click', function(e) {
                                        e.preventDefault();
                                        $currentopenlink = $(this);
                                        createmessagemodal.show();
                                    });

                                    return $html;
                                }
                            );
                        }

                        ModalFactory.create({
                            large: true,
                            body: detailview
                        })
                        .then(function(modal) {
                            modalresource = modal;
                            modal.getModal().addClass('mod_dataview-record-modal');
                            modal.show();

                            $open.data('modal', modalresource);
                        });
                    });

                    $recordsboard.append($content);
                });

            },
            fail: function (e) {
                Notification.exception(e);
                console.log(e);
            }
        }]);
    };

    return {
        init: init
    };
});
