import './extension/sw-product-detail-specifications';
import './extension/sw-product-list';
import './extension/sw-product-detail';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

Shopware.Locale.extend('de-DE', deDE);
Shopware.Locale.extend('en-GB', enGB);
