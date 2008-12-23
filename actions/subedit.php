<?php
/*
 * Laconica - a distributed open-source microblogging tool
 * Copyright (C) 2008, Controlez-Vous, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('LACONICA')) { exit(1); }

class SubeditAction extends Action {

    var $profile = NULL;

    function prepare($args) {

        parent::prepare($args);

        if (!common_logged_in()) {
            $this->client_error(_('Not logged in.'));
            return false;
        }

        $token = $this->trimmed('token');

        if (!$token || $token != common_session_token()) {
            $this->client_error(_('There was a problem with your session token. Try again, please.'));
            return;
        }

        $id = $this->trimmed('profile');

        if (!$id) {
            $this->client_error(_('No profile specified.'));
            return false;
        }

        $this->profile = Profile::staticGet('id', $id);

        if (!$this->profile) {
            $this->client_error(_('No profile with that ID.'));
            return false;
        }

        return true;
    }

    function handle($args) {
        parent::handle($args);
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $cur = common_current_user();

            $sub = Subscription::pkeyGet(array('subscriber' => $cur->id,
                                               'subscribed' => $this->profile->id));

            if (!$sub) {
                $this->client_error(_('You are not subscribed to that profile.'));
                return false;
            }

            $orig = clone($sub);

            $sub->jabber = $this->boolean('jabber');
            $sub->sms = $this->boolean('sms');

            $result = $sub->update($orig);

            if (!$result) {
                common_log_db_error($sub, 'UPDATE', __FILE__);
                $this->server_error(_('Could not save subscription.'));
                return false;
            }

            common_redirect(common_local_url('subscriptions',
                                             array('nickname' => $cur->nickname)));
        }
    }
}
