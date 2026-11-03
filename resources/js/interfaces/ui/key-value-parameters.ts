export interface ParameterContract {
    id?: number;
    type: ParameterType;
    key: string;
    value: string;
    enabled: boolean;
    file?: File;
}

export enum ParameterType {
    Text = 'text',
    File = 'file',
}
