export type GlobalException = {
    exception: ExceptionData;
    suggestedSolution?: string;
};

export interface ExceptionData {
    message: string;
    previous?: ExceptionPrevious | null;
}

export interface ExceptionPrevious {
    message: string;
    file?: string;
    line?: number;
    trace?: string;
}
