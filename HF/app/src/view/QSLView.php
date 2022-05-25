<?php

namespace QSL;

require_once 'lib.php';

/**
 * Render info for a single QSO/QSLs combination
 */
class QSLView
{
    public static function render(array $qso, ?array $qsl_sent, ?array $qsl_received)
    {
        // either ham_1 or ham_2 was the currently logged in user
        // so we switch them to $used_callsign (current users) and $other_callsign
        $used_callsign = $qso['ham_1_cs'] === \User\LoggedInUser::getCallsign() ? $qso['ham_1'] : $qso['ham_2'];
        $other_callsign = $qso['ham_2_cs'] === \User\LoggedInUser::getCallsign() ? $qso['ham_1'] : $qso['ham_2'];
        // base callsign of the other HAM
        $other_base = $qso['ham_2_cs'] === \User\LoggedInUser::getCallsign() ? $qso['ham_1_cs'] : $qso['ham_2_cs'];

        // basic properies of a QSO
        $datetime = $qso['dtime'];
        $qsoid = $qso['id'];
        $freq = formatFreq($qso['freq']);
        $mode = $qso['mode'];

        // use a similar trick to select correct reports
        $received_report = $qso['ham_1_cs'] === \User\LoggedInUser::getCallsign() ? $qso['report_1'] : $qso['report_2'];
        $sent_report = $qso['ham_2_cs'] === \User\LoggedInUser::getCallsign() ? $qso['report_1'] : $qso['report_2'];


        // sent QSL card
        $sent_qsl_id = is_null($qsl_sent) ? '' : $qsl_sent['id'];
        $sent_qsl_status = is_null($qsl_sent) ? '' : ($qsl_sent['accepted'] ? 'Accepted ✔' : 'Not accepted yet ⌛');
        // two cases: no QSL was sent - make a send button or QSL was already sent.
        $sent_qsl_card = is_null($qsl_sent) ?
        // no QSL was sent - send button
            <<<END
            <div class="card" style="width: 18rem;">
                <div class="card-header">
                    Sent QSL.
                </div>
                <div class="card-body">
                    <h5 class="card-title">No QSL sent (yet)</h5>
                    <form method="GET" action="/qsl/new" class="m-auto">
                        <input type="hidden" name="qsoid" value="$qsoid" />
                        <button type="submit" class="btn btn-success">Send QSO</button>
                    </form>
                </div>
            </div>
            END
        // QSL was sent - show image & status
        :
            <<<END
            <div class="card" style="width: 18rem;">
                <div class="card-header">
                    Sent QSL.
                </div>
                <img src="/qsl/image?id=$sent_qsl_id" class="card-img-top">
                <div class="card-body">
                    $sent_qsl_status
                </div>
            </div>
            END;

        /**
         * Received qsl - render it (if valid), with accept button or text saying it's already accepted
         */
        $received_qsl_id = is_null($qsl_received) ? '' : $qsl_received['id'];
        $received_qsl_status = is_null($qsl_received) ? '' : ($qsl_received['accepted'] ? 'Accepted ✔' : <<<END
        <form method="POST" action="/qsl/accept" class="m-auto">
            <input type="hidden" name="id" value="$received_qsl_id" />
            <button type="submit" class="btn btn-success">Accept ✔</button>
        </form>
        END);

        $received_qsl = is_null($qsl_received) ? '' : <<<END
        <div class="card" style="width: 18rem;">
            <div class="card-header">
                Received QSL.
            </div>
            <img src="/qsl/image?id=$received_qsl_id" class="card-img-top">
            <div class="card-body">
                $received_qsl_status
            </div>
        </div>
        END;


        /**
         * Put everything together
         */
        return <<<END
        <div class="card"">
            <div class="card-body">
                <h5 class="card-title">QSO with $other_base</h5>
                <p class="card-text">
                    Used callsign: $used_callsign<br>
                    Contact: $other_callsign<br>
                    Date & time: $datetime<br>
                    Technical details: $freq, $mode<br>
                    Report: $received_report (sent), $sent_report (received)
                </p>
                $sent_qsl_card
                $received_qsl
            </div>
        </div>
        END;
    }
}
