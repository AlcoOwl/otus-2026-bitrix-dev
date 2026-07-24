const wrapAction = function (timemanWindow, actionName) {
    const originalAction = timemanWindow.ACTIONS[actionName];
    const confirmButtonMessage = actionName === 'OPEN'
        ? 'OTUS_TIMEMAN_START_BUTTON'
        : 'OTUS_TIMEMAN_CONTINUE_BUTTON';

    timemanWindow.ACTIONS[actionName] = function (event) {
        BX.UI.Dialogs.MessageBox.confirm(
            BX.message('OTUS_TIMEMAN_CONFIRM_TEXT'),
            BX.message('OTUS_TIMEMAN_CONFIRM_TITLE'),
            function () {
                originalAction(event);
                return true;
            },
            BX.message(confirmButtonMessage),
            null,
            BX.message('OTUS_TIMEMAN_CANCEL_BUTTON'),
            true
        );

        return false;
    };
};

BX.addCustomEvent(window, 'onTimemanInit', function () {
    const timemanWindow = window.BXTIMEMAN.WND;

    wrapAction(timemanWindow, 'OPEN');
    wrapAction(timemanWindow, 'REOPEN');
});
