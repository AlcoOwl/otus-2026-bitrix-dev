let originalBxOnCustomEvent = BX.onCustomEvent;
BX.onCustomEvent = function (eventObject, eventName, eventParams, secureParams)
{
    // onMenuItemHover например выбрасывает в другом порядке
    let realEventName = BX.type.isString(eventName) ?
        eventName : BX.type.isString(eventObject) ? eventObject : null;
    if (realEventName) {
        console.log(
            '%c' + realEventName,
            'background: #222; color: #bada55; font-weight: bold; padding: 3px 4px;'
        );
    }
    console.dir({
        eventObject: eventObject,
        eventParams: eventParams,
        secureParams: secureParams
    });
    return originalBxOnCustomEvent.apply(
        this, arguments
    );
};
