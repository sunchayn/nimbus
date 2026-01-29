import type { ExceptionData } from '@/interfaces/common/exceptions';

export type { ExceptionPrevious, GlobalException } from '@/interfaces/common/exceptions';

export type RouteExtractorException = {
    exception: ExceptionData;
    routeContext: ExceptionRouteContext;
    suggestedSolution?: string;
    ignoreData?: string;
};

export interface ExceptionRouteContext {
    uri?: string;
    methods?: string[];
    controllerClass?: string;
    controllerMethod?: string;
}
