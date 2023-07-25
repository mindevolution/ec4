<?php

/*
 * This file is part of ContactManagement4
 *
 * Copyright(c) U-Mebius Inc. All Rights Reserved.
 *
 * https://umebius.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactManagement4;

final class PluginEvents
{
    // searchCustomer
    const ADMIN_CONTACT_EDIT_SEARCH_CUSTOMER_INITIALIZE = 'admin.contact.edit.search.customer.initialize';
    const ADMIN_CONTACT_EDIT_SEARCH_CUSTOMER_SEARCH = 'admin.contact.edit.search.customer.search';
    const ADMIN_CONTACT_EDIT_SEARCH_CUSTOMER_COMPLETE = 'admin.contact.edit.search.customer.complete';

    // searchCustomerById
    const ADMIN_CONTACT_EDIT_SEARCH_CUSTOMER_BY_ID_INITIALIZE = 'admin.contact.edit.search.customer.by.id.initialize';
    const ADMIN_CONTACT_EDIT_SEARCH_CUSTOMER_BY_ID_COMPLETE = 'admin.contact.edit.search.customer.by.id.complete';
}
