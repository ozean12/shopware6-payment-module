const ApiService = Shopware.Classes.ApiService;

/**
 * @class
 */
export default class BillieApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'billie') {
        super(httpClient, loginService, apiEndpoint);
        this.name = 'billieApiService';
    }

    testCredentials(id, secret, isSandbox) {
        return this.httpClient
            .post(`${this.getApiBasePath()}/test-credentials`,
                {
                    id: id,
                    secret: secret,
                    isSandbox: isSandbox
                },
                {
                    headers: this.getBasicHeaders()
                }
            ).then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}
