import BillieApiService from '../service/billie.api.service';

Shopware.Application.addServiceProvider('billieApiService', () => {
    const factoryContainer = Shopware.Application.getContainer('factory');
    const initContainer = Shopware.Application.getContainer('init');

    const apiServiceFactory = factoryContainer.apiService;
    const service = new BillieApiService(initContainer.httpClient, Shopware.Service('loginService'));
    const serviceName = service.name;
    apiServiceFactory.register(serviceName, service);

    return service;
});
