/* global define */
define(['oro/locale-settings', 'oro/formatter/name'],
function(localeSettings, nameFormatter) {
    'use strict';

    /**
     * Address formatter
     *
     * @export  oro/formatter/address
     * @class   oro.AddressFormatter
     */
    return {
        format: function(address, country, newLine) {
            if (!country) {
                if (localeSettings.isFormatAddressByAddressCountry()) {
                    country = address.countryIso2;
                } else {
                    country = localeSettings.getCountry();
                }
            }
            newLine = newLine || '<br/>';

            var format = this.getAddressFormat(country);
            var formatted = format.replace(/%(\w+)%/g, function (pattern, key) {
                var lowerCaseKey = key.toLowerCase();
                var value = '';
                if ('name' === lowerCaseKey) {
                    value = nameFormatter.format(address, localeSettings.getCountryLocale(country));
                } else if ('street' == lowerCaseKey) {
                    value = address.street + ' ' + address.street2;
                } else if ('street1' == lowerCaseKey) {
                    value = address.street;
                } else if ('country' == lowerCaseKey) {
                    value = address.countryName;
                } else {
                    value = address[lowerCaseKey];
                }
                if (value && key !== lowerCaseKey) {
                    value = value.toLocaleUpperCase();
                }
                return value;
            });

            var addressLines = formatted.split('\n');
            if (typeof newLine == 'function') {
                for (var i = 0; i < addressLines.length; i++) {
                    addressLines[i] = newLine(addressLines[i]);
                }
                return addressLines.join('');
            } else {
                addressLines.join('');
                return addressLines.join(newLine);
            }
        },

        getAddressFormat: function(country) {
            var addressFormats = localeSettings.getAddressFormats();
            if (!addressFormats.hasOwnProperty(country)) {
                country = localeSettings.getCountry();
            }
            return addressFormats[country];
        }
    }
});
