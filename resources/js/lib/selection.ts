/** `list` with `value` added (checked) or removed, without duplicates. */
export function toggleValue<T>(list: T[], value: T, checked: boolean): T[] {
    return toggleValues(list, [value], checked);
}

/** `list` with every one of `values` added (checked) or removed. */
export function toggleValues<T>(list: T[], values: T[], checked: boolean): T[] {
    return checked
        ? [...list, ...values.filter((value) => !list.includes(value))]
        : list.filter((value) => !values.includes(value));
}
